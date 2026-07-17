<?php

namespace Database\Factories;

use App\Models\ParentGuardian;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParentGuardian>
 */
class ParentGuardianFactory extends Factory
{
    protected $model = ParentGuardian::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+2567'.$this->faker->unique()->numerify('#######'),
            'password' => Hash::make('password'),
            'relationship' => 'guardian',
            'business_id' => null,
            'account_type' => 'guest',
            'status' => 'active',
            // universal_code generated in ParentGuardian::creating boot
        ];
    }

    public function linked(int $businessId): static
    {
        return $this->state(fn () => [
            'business_id' => $businessId,
            'account_type' => 'linked',
        ]);
    }
}
