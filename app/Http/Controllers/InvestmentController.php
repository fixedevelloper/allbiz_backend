<?php

namespace App\Http\Controllers;

use App\Models\Roulette;
use App\Models\Transaction;
use App\Models\User;
use App\Rules\PhoneNumber;
use App\Services\MoneyInService;
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
            'country_id'  => 'nullable|exists:countries,id',
        ]);

        $user = Auth::user();

        // ❌ Un seul investissement actif
        if ($user->investment) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà un investissement actif.',
            ], 400);
        }

        DB::beginTransaction();

        try {

            /** 🔐 Génération référence AVANT paiement */
            $referenceId = Str::uuid()->toString();

            /** 1️⃣ Créer transaction (PENDING) */
            $transaction = Transaction::create([
                'user_id'   => $user->id,
                'reference' => $referenceId,
                'amount'    => $request->amount,
                'status'    => 'pending',
                'type'      => 'investment',
            ]);

            /** 2️⃣ Créer investissement */
            $investment = Investment::create([
                'user_id'        => $user->id,
                'amount'         => $request->amount,
                'transaction_id'=> $transaction->id,
            ]);

            /** 3️⃣ Commission parrainage */
            $commission = null;
            if ($user->referrer_id) {
                $commission = $this->referralService->handleReferral($investment);
            }

            DB::commit();

            /** 4️⃣ Initier paiement (APRÈS COMMIT) */
            $this->moneyinService->initialise([
                'reference'   => $referenceId,
                'amount'      => $request->amount,
                'phone'       => $request->phone,
                'operator_id' => $request->operator_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Investissement initialisé avec succès',
                'referenceId' => $referenceId,
                'investment' => $investment,
                'commission' => $commission ? [
                    'amount' => $commission->amount,
                    'roulette_count' => $commission->roulette_count,
                    'roulette_gain' => $commission->roulettes->sum('amount'),
                ] : null,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error($e);

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
}
