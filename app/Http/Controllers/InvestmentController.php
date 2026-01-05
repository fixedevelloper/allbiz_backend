<?php

namespace App\Http\Controllers;

use App\Models\Roulette;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Investment;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InvestmentController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Créer un nouvel investissement (1 seul par utilisateur)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */


    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|in:1000,2000,5000,10000',
            'referrer_id' => 'nullable|integer|exists:users,id'
        ]);

        $user = Auth::user();

        // ❌ Un seul investissement
        if ($user->investment) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà un investissement actif.'
            ], 400);
        }

        // ❌ Auto-parrainage interdit
        if ($request->referrer_id && $request->referrer_id == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Auto-parrainage interdit.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            // 🔒 Définir le parrain UNE SEULE FOIS
            if (!$user->referrer_id && $request->referrer_id) {
                $user->update([
                    'referrer_id' => $request->referrer_id,
                    'membership_level'=>$request->amount
                ]);
            }
            $user->update([
                'membership_level'=>$request->amount
            ]);
            // 1️⃣ Créer l’investissement
            $investment = Investment::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
            ]);

            // 2️⃣ Calcul commission
            $commission = null;
            if ($user->referrer_id) {
                $commission = $this->referralService->handleReferral($investment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Investissement créé avec succès',
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
                'message' => 'Erreur lors de la création de l’investissement'
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
