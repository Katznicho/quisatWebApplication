<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\Currency;

class KidsMartFeatureSeeder extends Seeder
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
            'name' => 'KidsMart',
            'description' => 'Online marketplace for kids products. Allows businesses to upload products, manage inventory, process orders, and enable customers to shop online with delivery.',
            'price' => '100000', // 100,000 UGX
            'currency_id' => $currency->id,
        ];

        $existingFeature = Feature::where('name', $feature['name'])->first();
        
        if (!$existingFeature) {
            Feature::create($feature);
            $this->command->info("✅ Created feature: {$feature['name']}");
        } else {
            $this->command->warn("⚠️  Feature already exists: {$feature['name']}");
        }

        $this->command->info('🎉 KidsMart feature has been processed!');
    }
}

