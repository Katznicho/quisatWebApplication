<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\Currency;

class KidsEventsFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the default currency (UGX)
        $currency = Currency::first();
        
        if (!$currency) {
            $this->command->error('No currency found. Please run currency seeder first.');
            return;
        }

        $features = [
            [
                'name' => 'Chat & Communication',
                'description' => 'Real-time messaging system for internal communication between staff, students, and parents. Includes group chats, direct messaging, file sharing, and notification system.',
                'price' => '50000', // 50,000 UGX
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Business Advertising',
                'description' => 'Comprehensive advertising management system for promoting school events, programs, and services. Includes ad creation, targeting, scheduling, analytics, and performance tracking.',
                'price' => '75000', // 75,000 UGX
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Kids Events Management',
                'description' => 'Complete event management system for organizing and managing kids events. Features include event creation, participant management, registration, payment tracking, age group targeting, and event analytics.',
                'price' => '100000', // 100,000 UGX
                'currency_id' => $currency->id,
            ],
        ];

        foreach ($features as $featureData) {
            // Check if feature already exists
            $existingFeature = Feature::where('name', $featureData['name'])->first();
            
            if (!$existingFeature) {
                Feature::create($featureData);
                $this->command->info("✅ Created feature: {$featureData['name']}");
            } else {
                $this->command->warn("⚠️  Feature already exists: {$featureData['name']}");
            }
        }

        $this->command->info('🎉 All features have been processed!');
    }
}