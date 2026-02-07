<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Roulette;
use App\Models\Transaction;
use App\Models\User;
use App\Rules\PhoneNumber;
use App\Services\FedaPayService;
use App\Services\MoneyInService;
use App\Services\SoftpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Investment;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvestmentController extends Controller
{
    protected $referralService;
    protected $moneyinService;

    public function __construct(MoneyInService $moneyInService,ReferralService $referralService)
    {
        $this->referralService = $referralService;
        $this->moneyinService=$moneyInService;
    }

    /**
     * Créer un nouvel investissement (1 seul par utilisateur)
     * @param Request $request
     * @return JsonResponse
     */


    public function store(Request $request)
    {
        $request->validate([
            'amount'      => 'required|integer|in:1000,2000,5000,10000',
            'phone'       => ['required', new PhoneNumber],
            'operator_id' => 'required|exists:operators,id',
            'meta.name'   => 'required|string|max:255',
            'meta.email'  => 'required|email|max:255',
        ]);

        $user = Auth::user();
/*
        if ($user->investments()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà un investissement actif.',
            ], 400);
        }*/

        if ($user->investment && $user->investment->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà un investissement actif.',
            ], 400);
        }


        DB::beginTransaction();

        try {
            $referenceId = Str::uuid()->toString();

            /** 1️⃣ Transaction */
            $transaction = Transaction::create([
                'user_id'   => $user->id,
                'amount'    => $request->amount,
                'status'    => 'pending',
                'type'      => 'investment',
                'reference' => $referenceId,
            ]);

            /** 2️⃣ Investissement */
            $investment = Investment::create([
                'user_id'        => $user->id,
                'amount'         => $request->amount,
                'transaction_id' => $transaction->id,
            ]);

            /** 3️⃣ Commission */
            $commission = null;
            if ($user->referrer_id) {
             //   $commission = $this->referralService->handleReferral($investment);
            }



            /** 4️⃣ Paiement FedaPay */
            $operator = Operator::with('country')->findOrFail($request->operator_id);

            $fedaPay = new FedaPayService();

            if (is_null($user->code)){
                /** Client */
                $customer = $fedaPay->createCustomer([
                    'firstname' => $request->meta['name'],
                    'lastname'  => $request->meta['name'],
                    'email'     => $request->meta['email'],
                    'phone'     => $request->phone,
                    'country'   => $operator->country->iso,
                ]);
                if (empty($customer->id)) {
                    throw new \Exception('Création client FedaPay échouée');
                }
                $user->update([
                    'code'=>$customer->id
                ]);
            }


            /** Paiement */
            $payment = $fedaPay->collect([
                'amount'       => $request->amount,
                'phone'        => $request->phone,
                'method'       => $operator->code, // mtn, orange_money, moov
                'customer_id'  => $user->code,
                'callback_url' => route('softpay.callback'),
            ]);
            $paymentId = $payment->data->{'v1/transaction'}->id ?? null;
            $paymentRef = $payment->data->{'v1/transaction'}->reference ?? null;
            $paymentUrl = $payment->data->{'v1/transaction'}->payment_url ?? null;
            //logger(json_encode($payment));
            /** Sauvegarde infos paiement */
            $transaction->update([
                'reference'=>$paymentId,
                'meta'=>[
                    'paymentId' => $paymentId ?? null,
                    'reference'        =>$paymentRef,
                    'payment_url'        =>$paymentUrl,
                ]
            ]);
            DB::commit();
            return response()->json([
                'success'     => true,
                'message'     => 'Paiement initié',
                'referenceId' => $paymentId,
                'payment_url' => $paymentUrl ?? null,
                'investment'  => $investment,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l’investissement',
            ], 500);
        }
    }




    public function spin(Request $request, Roulette $roulette)
    {
        // 🔐 Vérifier propriétaire
        if ($roulette->commission->referrer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }

        // ⛔ Déjà jouée
        if ($roulette->status) {
            return response()->json([
                'message' => 'Roulette déjà jouée'
            ], 403);
        }

        // 🎯 Gains possibles (DOIT matcher le frontend)
        $gains = match ($roulette->type) {
        '1step' => [300, 350, 400, 450, 500],
        '2step' => [800, 850, 900, 950, 1000],
        default => []
    };

    if (empty($gains)) {
        return response()->json([
            'message' => 'Type de roulette invalide'
        ], 400);
    }

    // 🎲 Tirage sécurisé
    $gain = $gains[array_rand($gains)];

    DB::transaction(function () use ($roulette, $gain) {
        // 🎡 Mise à jour roulette
        $roulette->update([
            'amount' => $gain,
            'status' => true,
            'executed_at' => now(),
        ]);

        // 💰 Crédit du parrain
        if ($roulette->commission && $roulette->commission->referrer) {
            $roulette->commission
                ->referrer
                ->increment('balance', $gain);
        }
    });

    return response()->json([
        'gain' => $gain
    ]);
}



    /**
     * Liste des investissements de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();

        $investments = Investment::with('commissions.roulettes')
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'success' => true,
            'investments' => $investments
        ]);
    }
    public function summary()
    {
        $user = auth()->user()->load('referrer', 'investment');

        // Filleuls avec montant investi
        $referrals = User::where('referrer_id', $user->id)
            ->with('investment')
            ->get()
            ->map(function ($ref) {
                return [
                    'id' => $ref->id,
                    'phone' => $ref->phone,
                    'investment_amount' => $ref->investment?->amount ?? 0,
            ];
        });

        return response()->json([
                'user' => [
                    'phone' => $user->phone,
                    'membership_level' => $user->membership_level,
                    'investment_amount' => $user->investment?->amount ?? 0,
        ],
        'referrer' => $user->referrer ? [
        'phone' => $user->referrer->phone,
    ] : null,
        'referrals' => $referrals,
    ]);
}
}
