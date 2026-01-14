<?php

namespace App\Http\Controllers;

use App\Models\Roulette;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function myReferrals(Request $request)
    {
        $user = Auth::user();

        $referrals = $user->referrals()
            ->with([
                'investment.commissions.roulettes'
            ])
            ->get()
            ->map(function ($ref) use ($user) {

                // 🔑 récupérer la commission du parrain courant
                $commission = $ref->investment?->commissions
                ->where('referrer_id', $user->id)
                    ->first();

            return [
                'id' => $ref->id,
                'name' => $ref->name,
                'phone' => $ref->phone,

                'investment' => $ref->investment?->amount ?? 0,

                'commission' => $commission?->amount ?? 0,
                'roulette_count' => $commission?->roulette_count ?? 0,
                'roulette_gain' => $commission?->roulettes->sum('amount') ?? 0,

                'joined_at' => $ref->created_at->format('d/m/Y'),
            ];
        });

        return response()->json([
            'total_referrals' => $referrals->count(),
            'total_commission' => $user->commissions()->sum('amount'),
            'data' => $referrals
        ]);
    }
    public function myTransactions(Request $request)
    {
        $withdraws = Transaction::where('user_id', $request->user()->id)
            ->where('type', 'withdrawal')
            ->latest()
            ->get()
            ->map(function ($w) {

                $operatorName = $w->meta['operator'] ?? null;

                return [
                    'id' => $w->id,
                    'amount' => $w->amount,
                    'status' => $w->status,
                    'created_at' => $w->created_at,
                    'operator' => $operatorName ? [
                        'name' => $operatorName,

                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $withdraws
        ]);
    }

    public function myCommissions(Request $request)
    {
        $commissions = $request->user()
            ->commissions() // récupère toutes les commissions où l'utilisateur est le referrer
            ->with(['referrer:id,phone', 'investment:id,amount'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'phone' => $commission->referrer->phone ?? null,
                    'investment_amount' => $commission->investment->amount ?? 0,
                    'roulette_count' => $commission->roulette_count,
                    'amount' => $commission->amount,
                    'created_at' => $commission->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $commissions
        ]);
    }

    public function myRoulettes(Request $request)
    {
        $roulettes = $request->user()
            ->roulettes()
            ->select(
                'roulettes.id',
                'roulettes.amount',
                'roulettes.status',
                'roulettes.type',
                'roulettes.executed_at',
                'roulettes.created_at'
            )
            ->orderByDesc('roulettes.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roulettes
        ]);
    }
    public function myRouletteById(Request $request,$id)
    {
        logger($id);
/*        $roulette = Roulette::query()
            ->where('id', $id)
            ->where('status', false)
            ->firstOrFail();*/

        return response()->json([
            'data' => Roulette::find($id)
        ]);
    }

}
