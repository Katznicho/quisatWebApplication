<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\Currency;

class ParentCornerFeatureSeeder extends Seeder
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
            'name' => 'Parent Corner',
            'description' => 'System for organizing parent workshops, seminars, support groups, and training sessions. Features include event creation, parent registration, payment tracking, and participant management.',
            'price' => '95000', // 95,000 UGX
            'currency_id' => $currency->id,
        ];

        $existingFeature = Feature::where('name', $feature['name'])->first();
        
        if (!$existingFeature) {
            Feature::create($feature);
            $this->command->info("✅ Created feature: {$feature['name']}");
        } else {
            $this->command->warn("⚠️  Feature already exists: {$feature['name']}");
        }

        $this->command->info('🎉 Parent Corner feature has been processed!');
    }
}
