<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Commission;
use App\Models\Investment;
use App\Models\User;

class CommissionSeeder extends Seeder
{
    public function run(): void
    {
        $investments = Investment::with('user')->get();

        foreach ($investments as $investment) {
            $user = $investment->user;

            if (!$user->referrer_id) continue;

            $referrer = User::find($user->referrer_id);

            if (!$referrer) continue;

            // Logique simple de commission
            $commissionAmount = 500;
            $rouletteCount = 0;

            if ($investment->amount >= 5000) {
                $rouletteCount = 1;
            }
            if ($investment->amount >= 10000) {
                $rouletteCount = 2;
            }

            Commission::create([
                'referrer_id' => $referrer->id,
                'investment_id' => $investment->id,
                'amount' => $commissionAmount,
                'roulette_count' => $rouletteCount,
            ]);

            // Créditer le parrain
            $referrer->increment('balance', $commissionAmount);
        }
    }
}
