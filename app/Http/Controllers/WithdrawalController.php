<?php


namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Jobs\ProcessWithdrawalPayment;
use App\Models\Country;
use App\Models\Operator;
use App\Models\Transaction;
use App\Models\WithdrawAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function index()
    {
        // Charge les opérateurs en même temps pour éviter le N+1
        $countries = Country::with('operators')->get();

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }
    public function operators()
    {
        // Charge les opérateurs en même temps pour éviter le N+1
        $operators = Operator::query()->get();

        return response()->json([
            'success' => true,
            'data' => $operators
        ]);
    }
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
            'withdraw_account_id' => 'required|integer',
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            // ✅ Vérifier retrait en attente
            $pending = Transaction::where('user_id', $user->id)
                ->where('type', 'withdrawal')
                ->where('status', 'pending')
                ->exists();

            if ($pending) {
                return response()->json([
                    'message' => 'Un retrait est déjà en cours de traitement'
                ], 400);
            }

            // ✅ Vérifier le compte
            $account = WithdrawAccount::where('id', $request->withdraw_account_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$account) {
                return response()->json([
                    'message' => 'Compte de retrait invalide'
                ], 400);
            }

            // ✅ Solde disponible
            $availableBalance = $user->balance;
            if ($request->amount > $availableBalance) {
                return response()->json(['message' => 'Solde insuffisant'], 400);
            }

            // ✅ Calcul taxe
            $tax = (int) round($request->amount * 0.10);
            $netAmount = $request->amount - $tax;

            // ✅ Créer la transaction
            $withdrawal = Transaction::create([
                'user_id'   => $user->id,
                'reference' => uniqid('WD-'),
                'amount'    => $request->amount,
                'type'      => 'withdrawal',
                'status'    => 'pending',
                'meta'      => [
                    'account_withdraw_id'=>$account->id,
                    'account_id' => $account->id,
                    'operator'   => $account->operator->name ?? null,
                    'phone'      => $account->phone,
                    'tax'        => $tax,
                    'net_amount' => $netAmount,
                ],
            ]);

            // ✅ Mettre à jour le solde
            $user->decrement('balance', $request->amount);

            // ✅ Job asynchrone
            ProcessWithdrawalPayment::dispatch($withdrawal)->delay(now()->addSeconds(5));

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
            return response()->json(['message' => 'Erreur lors du retrait'], 500);
        }

    }
}

