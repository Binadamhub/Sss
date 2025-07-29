<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InvestmentPlan;

class InvestmentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plan A',
                'profit_percentage' => 30.00,
                'duration_days' => 5,
                'minimum_amount' => 1000.00,
                'maximum_amount' => null,
                'is_active' => true,
                'description' => 'Earn 30% profit in just 5 days. Perfect for short-term investments.',
            ],
            [
                'name' => 'Plan B',
                'profit_percentage' => 60.00,
                'duration_days' => 7,
                'minimum_amount' => 5000.00,
                'maximum_amount' => null,
                'is_active' => true,
                'description' => 'Earn 60% profit in 7 days. Higher returns for patient investors.',
            ],
        ];

        foreach ($plans as $plan) {
            InvestmentPlan::create($plan);
        }
    }
}

