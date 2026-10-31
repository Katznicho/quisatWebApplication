<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Feature;
use App\Services\PocketPayService;
use Illuminate\Database\Seeder;

class PocketPayFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::first();

        if (! $currency) {
            $this->command->error('No currency found. Please run currency seeder first.');

            return;
        }

        $feature = [
            'name' => PocketPayService::FEATURE_NAME,
            'description' => 'Cashless school canteens: student cards with PINs and daily limits, canteen floats, the Quisat Attendant app and parent top-ups by mobile money.',
            'price' => '0',
            'currency_id' => $currency->id,
        ];

        if (! Feature::where('name', $feature['name'])->exists()) {
            Feature::create($feature);
            $this->command->info("✅ Created feature: {$feature['name']}");
        } else {
            $this->command->warn("⚠️  Feature already exists: {$feature['name']}");
        }

        $this->command->info('Enable "'.PocketPayService::FEATURE_NAME.'" on a school from Features, then create canteens and assign cards.');
    }
}
