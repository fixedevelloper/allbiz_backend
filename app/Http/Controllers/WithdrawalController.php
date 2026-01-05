<?php


namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Jobs\ProcessWithdrawalPayment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    /**
     * Fourchettes autorisées
     */
    private array $allowedAmounts = [
        1000,
        2000,
        5000,
        10000,
        20000,
        50000,
        75000,
        100000
    ];

    /**
     * Créer une demande de retrait
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount'   => ['required', 'integer', 'in:' . implode(',', $this->allowedAmounts)],
            'operator' => 'required|string',
            'phone'    => 'required|string|min:8',
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            /** 1️⃣ Vérifier retrait en attente */
            $pending = Transaction::where('user_id', $user->id)
                ->where('type', 'withdrawal')
                ->where('status', 'pending')
                ->exists();

            if ($pending) {
                return response()->json([
                    'message' => 'Un retrait est déjà en cours de traitement'
                ], 400);
            }

            /** 2️⃣ Calcul solde disponible */
            $totalCommission = Transaction::where('user_id', $user->id)
                ->where('type', 'commission')
                ->where('status', 'success')
                ->sum('amount');

            $totalWithdrawn = Transaction::where('user_id', $user->id)
                ->where('type', 'withdrawal')
                ->whereIn('status', ['pending', 'success'])
                ->sum('amount');

          //  $availableBalance = $totalCommission - $totalWithdrawn;
            $availableBalance = $user->balance;

            if ($request->amount > $availableBalance) {
                return response()->json([
                    'message' => 'Solde insuffisant'
                ], 400);
            }

            /** 3️⃣ Calcul taxe */
            $tax = (int) round($request->amount * 0.10);
            $netAmount = $request->amount - $tax;

            /** 4️⃣ Créer transaction retrait */
            $withdrawal = Transaction::create([
                'user_id'   => $user->id,
                'reference' => uniqid('WD-'),
                'amount'    => $request->amount,
                'type'      => 'withdrawal',
                'status'    => 'pending',
                'meta'      => [
                    'operator'   => $request->operator,
                    'phone'      => $request->phone,
                    'tax'        => $tax,
                    'net_amount' => $netAmount,
                ],
            ]);
            $user->update([
               'balance'=> $availableBalance-$netAmount
            ]);
            ProcessWithdrawalPayment::dispatch($withdrawal)
                ->delay(now()->addSeconds(5));
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Demande de retrait envoyée avec succès',
                'data' => [
                    'amount_requested' => $request->amount,
                    'tax'              => $tax,
                    'amount_received'  => $netAmount,
                    'status'           => 'pending',
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            logger()->error($e);

            return response()->json([
                'message' => 'Erreur lors du retrait'
            ], 500);
        }
    }
}

