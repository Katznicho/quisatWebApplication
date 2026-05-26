<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Feature;
use Illuminate\Database\Seeder;

class KidsClinicsFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::first();

        if (! $currency) {
            $this->command->error('No currency found. Please run currency seeder first.');

            return;
        }

        $feature = [
            'name' => 'Kids Clinics',
            'description' => 'Pediatric clinic module: register children, manage families with access codes, and link parents without duplicate registration.',
            'price' => '0',
            'currency_id' => $currency->id,
        ];

        $existingFeature = Feature::where('name', $feature['name'])->first();

        if (! $existingFeature) {
            Feature::create($feature);
            $this->command->info("✅ Created feature: {$feature['name']}");
        } else {
            $this->command->warn("⚠️  Feature already exists: {$feature['name']}");
        }

        $this->command->info('🎉 Kids Clinics feature has been processed!');
        $this->command->info('Enable "Kids Clinics" on a business from Features, then run: php artisan db:seed --class=KidsClinicsDemoSeeder');
    }
}
