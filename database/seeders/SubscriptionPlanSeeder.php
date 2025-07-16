<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run()
    {
        SubscriptionPlan::create([
            'name' => 'Basic',
            'price' => 99,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'max_trading_pairs' => 5,
                'ai_market_analysis' => true,
                'smart_risk_management' => true,
            ]),
            'is_custom' => false,
        ]);

        SubscriptionPlan::create([
            'name' => 'Pro',
            'price' => 299,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'max_trading_pairs' => 'unlimited',
                'ai_market_analysis' => true,
                'smart_risk_management' => true,
                'advanced_analytics' => true,
                'priority_support' => true,
            ]),
            'is_custom' => false,
        ]);

        SubscriptionPlan::create([
            'name' => 'Enterprise',
            'price' => null, // Custom pricing
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'custom_ai' => true,
                'dedicated_team' => true,
                'advanced_integration' => true,
                'enterprise_security' => true,
                'ai_strategy_optimization' => true,
            ]),
            'is_custom' => true,
        ]);
    }
}
