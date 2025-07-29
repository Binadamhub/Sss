<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@smtxchange.com',
            'phone' => '+2348000000000',
            'email_verified_at' => now(),
            'password' => Hash::make('admin123'),
            'balance' => 0.00,
            'referral_bonus' => 0.00,
            'referral_code' => 'ADMIN001',
            'referred_by' => null,
            'is_active' => true,
            'is_admin' => true,
        ]);

        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'user@smtxchange.com',
            'phone' => '+2348111111111',
            'email_verified_at' => now(),
            'password' => Hash::make('user123'),
            'balance' => 10000.00,
            'referral_bonus' => 0.00,
            'referral_code' => 'USER001',
            'referred_by' => null,
            'is_active' => true,
            'is_admin' => false,
        ]);
    }
}

