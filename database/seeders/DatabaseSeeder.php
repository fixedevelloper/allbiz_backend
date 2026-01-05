<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\Tour;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            InvestmentSeeder::class,
            CommissionSeeder::class,
            RouletteSeeder::class,
        ]);
    }
}
