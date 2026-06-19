<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Feature;
use Illuminate\Database\Seeder;

class StationeryHubFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::first();

        if (! $currency) {
            $this->command->error('No currency found. Please run currency seeder first.');

            return;
        }

        $feature = [
            'name' => 'StationeryHub',
            'description' => 'Back to School Stationery Hub — list grade-tagged supplies, uniforms, and books for parents to shop in the app.',
            'price' => '100000',
            'currency_id' => $currency->id,
        ];

        if (! Feature::where('name', $feature['name'])->exists()) {
            Feature::create($feature);
            $this->command->info("Created feature: {$feature['name']}");
        } else {
            $this->command->warn("Feature already exists: {$feature['name']}");
        }
    }
}
