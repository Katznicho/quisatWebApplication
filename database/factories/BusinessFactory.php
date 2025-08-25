<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'country' => $this->faker->country(),
            'city' => $this->faker->city(),
            'business_category_id' => BusinessCategory::factory(),
            'logo' => null,
            'account_number' => 'KS' . time(),
            'account_balance' => 0,
            'mode' => 'live',
            'date' => now(),
            'enabled_feature_ids' => [],
        ];
    }
}
