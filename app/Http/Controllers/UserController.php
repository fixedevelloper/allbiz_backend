<?php

namespace App\Http\Controllers;

use App\Models\WithdrawAccount;
use Illuminate\Http\JsonResponse;
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
    public function balance(Request $request)
    {
        return response()->json([
            'balance' => $request->user()->balance,
        ]);
    }

    /**
     * Lister les comptes de retrait de l'utilisateur connecté
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $accounts = $request->user()->withdrawAccounts()->with('operator')->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Ajouter un compte de retrait
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'account_number' => 'nullable|string|max:50',
            'operator_id' => 'required|exists:operators,id',
        ]);

        $account = WithdrawAccount::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'account_number' => $request->account_number,
            'operator_id' => $request->operator_id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $account,
        ]);
    }
}
