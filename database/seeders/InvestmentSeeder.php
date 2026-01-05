<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Investment;
use App\Models\User;

class InvestmentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'customer')->get();

        foreach ($users as $user) {
            Investment::create([
                'user_id' => $user->id,
                'amount' => $user->membership_level,
            ]);
        }
    }
}
