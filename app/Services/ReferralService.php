<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Investment;
use App\Models\Commission;
use App\Models\Roulette;
use Illuminate\Support\Str;

class ReferralService
{
    // Tableau des commissions et roulettes selon le niveau du parrain
    protected $levels = [
        1000 => [
            1000 => ['amount' => 500, 'roulette' => 0],
            2000 => ['amount' => 500, 'roulette' => 0],
            5000 => ['amount' => 500, 'roulette' => 1],
            10000 => ['amount' => 500, 'roulette' => 2],
        ],
        2000 => [
            1000 => ['amount' => 500, 'roulette' => 0],
            2000 => ['amount' => 1000, 'roulette' => 0],
            5000 => ['amount' => 1000, 'roulette' => 1],
            10000 => ['amount' => 1000, 'roulette' => 2],
        ],
        5000 => [
            1000 => ['amount' => 500, 'roulette' => 0],
            2000 => ['amount' => 500, 'roulette' => 0],
            5000 => ['amount' => 2500, 'roulette' => 1],
            10000 => ['amount' => 2500, 'roulette' => 2],
        ],
        10000 => [
            1000 => ['amount' => 500, 'roulette' => 0],
            2000 => ['amount' => 1000, 'roulette' => 0],
            5000 => ['amount' => 2500, 'roulette' => 1],
            10000 => ['amount' => 5000, 'roulette' => 2],
        ],
    ];

    // Roulette ranges
    protected $rouletteRanges = [
        1 => [300, 500],
        2 => [800, 1000],
    ];

    /**
     * Traite la commission et les roulettes pour un investissement
     * @param Investment $investment
     * @return |null |null
     */
    public function handleReferral(Investment $investment)
    {
        $user = $investment->user;
        $referrer = $user->referrer;

        if (!$referrer) {
            return null; // pas de parrain
        }

        $parrainLevel = $referrer->membership_level;
        $investAmount = $investment->amount;

        // Vérifie si on a des règles pour ce niveau
        if (!isset($this->levels[$parrainLevel][$investAmount])) {
            return null;
        }

        $config = $this->levels[$parrainLevel][$investAmount];
        $commissionAmount = $config['amount'];
        $rouletteCount = $config['roulette'];

        // Créer la commission
        $commission = Commission::create([
            'referrer_id' => $referrer->id,
            'investment_id' => $investment->id,
            'amount' => $commissionAmount,
            'roulette_count' => $rouletteCount,
        ]);

        Roulette::create([
            'commission_id' => $commission->id,
            'type' => $rouletteCount>1?'2step':'1step',
        ]);

        Transaction::create([
            'user_id'   => $referrer->id,
            'reference' => 'COM-' . strtoupper(Str::random(10)),
            'amount'    => $commissionAmount,
            'type'      => 'commission',
            'status'    => 'success',
        ]);
        // Ajouter le montant au balance du parrain
        $totalRoulette = $commission->roulettes()->sum('amount');
        $referrer->balance += $commissionAmount + $totalRoulette;
        $referrer->save();

        return $commission;
    }
}
