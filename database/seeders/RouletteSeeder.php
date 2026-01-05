<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Commission;
use App\Models\Roulette;

class RouletteSeeder extends Seeder
{
    public function run(): void
    {
        $commissions = Commission::where('roulette_count', '>', 0)->get();

        foreach ($commissions as $commission) {
            for ($i = 0; $i < $commission->roulette_count; $i++) {
                Roulette::create([
                    'commission_id' => $commission->id,
                    'amount' => rand(300, 500), // gain roulette
                ]);
            }
        }
    }
}
