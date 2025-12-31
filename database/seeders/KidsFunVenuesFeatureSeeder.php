<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\Currency;

class KidsFunVenuesFeatureSeeder extends Seeder
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

        $feature = [
            'name' => 'Kids Fun Venues',
            'description' => 'System for managing fun venues for kids. Businesses can create venue listings with location, hours, activities, pricing, images, and booking info. Parents can discover fun places for children.',
            'price' => '85000', // 85,000 UGX
            'currency_id' => $currency->id,
        ];

        $existingFeature = Feature::where('name', $feature['name'])->first();
        
        if (!$existingFeature) {
            Feature::create($feature);
            $this->command->info("✅ Created feature: {$feature['name']}");
        } else {
            $this->command->warn("⚠️  Feature already exists: {$feature['name']}");
        }

        $this->command->info('🎉 Kids Fun Venues feature has been processed!');
    }
}
