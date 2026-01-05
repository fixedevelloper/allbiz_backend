<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function dashboardServer()
    {
        $user=Auth::user();
        $investments = $user->commissions()
            ->with('investment:id,amount,user_id') // seulement les colonnes nécessaires
            ->get();

        $referrals = $user->referrals()->count();

        $roulettesCount = $user->roulettes()->count();
        $roulettesGain  = $user->roulettes()->sum('roulettes.amount');
        $commissionCount = $user->commissions()->count();
        $ommissionsGain = $user->commissions()->sum('amount');
        $recentActivities = $user->transactions()->latest()->take(5)->get();

        return response()->json([
            "balance" => $user->balance,
            "membership_level" => $user->membership_level,
            "investments" => $investments,
            "referrals" => $referrals,
            "roulettes" => [
                "count" => $roulettesCount,
                "gain" => $roulettesGain
            ],
            "commissions" => [
                "count" => $roulettesCount,
                "gain" => $ommissionsGain
            ],
            "recentActivities" => $recentActivities
        ]);
    }
    public function referralLink(Request $request)
    {
        return response()->json([
            'referral_link' => $request->user()->referralLink(),
        ]);
    }

}
