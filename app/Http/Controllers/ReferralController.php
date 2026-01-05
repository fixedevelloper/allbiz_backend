<?php

namespace App\Http\Controllers;

use App\Models\Roulette;
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
        $transactions = $request->user()
            ->transactions()
            ->latest()
            ->get();

        return response()->json([
            'total_commission' => $request->user()
                ->transactions()
                ->where('type', 'commission')
                ->where('status', 'success')
                ->sum('amount'),

            'total_withdrawn' => $request->user()
                ->transactions()
                ->where('type', 'withdrawal')
                ->where('status', 'success')
                ->sum('amount'),

            'data' => $transactions,
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
        $roulette = Roulette::query()
            ->where('id', $id)
            ->where('status', false)
            ->firstOrFail();

        return response()->json([
            'data' => $roulette
        ]);
    }

}
