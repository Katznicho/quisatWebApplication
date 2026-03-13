<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\Currency;

class SupportChildFeatureSeeder extends Seeder
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
            'name' => 'Support Child',
            'description' => 'Module for child sponsorship. Lets organisations list children in need, with photos, stories, fees and contact details so parents and staff can follow up and support a child.',
            'price' => '0', // initially free / bundled; adjust later if needed
            'currency_id' => $currency->id,
        ];

        $existingFeature = Feature::where('name', $feature['name'])->first();
        
        if (!$existingFeature) {
            Feature::create($feature);
            $this->command->info("✅ Created feature: {$feature['name']}");
        } else {
            $this->command->warn("⚠️  Feature already exists: {$feature['name']}");
        }

        $this->command->info('🎉 Support Child feature has been processed!');
    }
}

