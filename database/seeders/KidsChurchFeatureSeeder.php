<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Feature;
use Illuminate\Database\Seeder;

class KidsChurchFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::query()->first();
        if (! $currency) {
            $currency = Currency::create([
                'name' => 'Ugandan Shilling',
                'code' => 'UGX',
                'symbol' => 'UGX',
                'rate' => '1',
                'status' => 'active',
                'is_default' => true,
            ]);
        }

        $currencyId = $currency->id;

        $features = [
            [
                'name' => 'Kids Church',
                'description' => 'Children’s ministry dashboard: Sunday check-in, pickup codes, lessons, volunteers, memory wall, and church events mapped onto Quisat school modules.',
                'price' => '0',
            ],
            [
                'name' => 'Quisat Moments',
                'description' => 'Private class and event photo albums. Teachers upload once; parents of that class see, like, and comment. Photos stay inside Quisat.',
                'price' => '0',
            ],
        ];

        foreach ($features as $feature) {
            Feature::firstOrCreate(
                ['name' => $feature['name']],
                [
                    'description' => $feature['description'],
                    'price' => $feature['price'],
                    'currency_id' => $currencyId,
                ]
            );
        }
    }
};
