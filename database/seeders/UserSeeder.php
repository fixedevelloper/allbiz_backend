<?php

namespace Database\Seeders;

use App\Models\Operator;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin',
            'phone' => '690000000',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'membership_level' => 10000,
            'balance' => 0,
        ]);

        // Parrain principal
        $referrer = User::create([
            'name' => 'Parrain',
            'phone' => '690000001',
            'email' => 'parrain@test.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'country_code' => 'CM',
            'membership_level' => 5000,
            'balance' => 0,
        ]);

        // Filleuls
        User::create([
            'name' => 'Filleul 1',
            'phone' => '690000002',
            'email' => 'filleul1@test.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'membership_level' => 10000,
            'referrer_id' => $referrer->id,
            'balance' => 0,
        ]);

        User::create([
            'name' => 'Filleul 2',
            'phone' => '690000003',
            'email' => 'filleul2@test.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'membership_level' => 5000,
            'referrer_id' => $referrer->id,
            'balance' => 0,
        ]);
        Operator::insert([
            ['name' => 'MTN', 'code' => 'mtn', 'country_code' => 'CM'],
            ['name' => 'Orange', 'code' => 'orange', 'country_code' => 'CM'],
            ['name' => 'Moov', 'code' => 'moov', 'country_code' => 'CI'],
        ]);

    }
}
