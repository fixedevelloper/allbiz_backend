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

    public function handleReferral(Investment $investment)
    {
        $user = $investment->user;
        $referrer = $user->referrer;

        if (!$referrer) {
            return null; // pas de parrain
        }

        $this->processReferral($referrer, $investment, 1);

        return true;
    }

    /**
     * Traite les commissions pour un parrain donné et ses niveaux supérieurs
     * @param User $referrer
     * @param Investment $investment
     * @param int $level
     */
    protected function processReferral($referrer, $investment, $level = 1)
    {
        $investAmount = (int) $investment->amount;

        // Commission du premier niveau via ton tableau existant
        if ($level === 1) {
            $parrainLevel = $referrer->membership_level;

            if (isset($this->levels[$parrainLevel][$investAmount])) {
                $config = $this->levels[$parrainLevel][$investAmount];
                $commissionAmount = $config['amount'];
                $rouletteCount = $config['roulette'];

                $this->createCommission($referrer, $investment, $commissionAmount, $rouletteCount);
            }
        }
        // Niveaux 2 et 3
        else if ($level === 2) {
            $commissionAmount = $investAmount * 0.20; // 20 % du montant du filleul
            $rouletteCount = 0; // pas de roulette pour les niveaux supérieurs
            $this->createCommission($referrer, $investment, $commissionAmount, $rouletteCount);
        }
        else if ($level === 3) {
            $commissionAmount = $investAmount * 0.10; // 10 % du montant du filleul
            $rouletteCount = 0;
            $this->createCommission($referrer, $investment, $commissionAmount, $rouletteCount);
        }

        // Passer au niveau supérieur
        if ($referrer->referrer && $level < 3) {
            $this->processReferral($referrer->referrer, $investment, $level + 1);
        }
    }

    /**
     * Crée la commission, la transaction et la roulette
     * @param $referrer
     * @param $investment
     * @param $commissionAmount
     * @param $rouletteCount
     */
    protected function createCommission($referrer, $investment, $commissionAmount, $rouletteCount)
    {
        $commission = Commission::create([
            'referrer_id' => $referrer->id,
            'investment_id' => $investment->id,
            'amount' => $commissionAmount,
            'roulette_count' => $rouletteCount,
        ]);

        if ($rouletteCount > 0) {
            Roulette::create([
                'commission_id' => $commission->id,
                'type' => $rouletteCount > 1 ? '2step' : '1step',
            ]);
        }

        Transaction::create([
            'user_id'   => $referrer->id,
            'reference' => 'COM-' . strtoupper(Str::random(10)),
            'amount'    => $commissionAmount,
            'type'      => 'commission',
            'status'    => 'success',
        ]);

        $referrer->balance += $commissionAmount;
        $referrer->save();
    }

}
