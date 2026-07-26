<?php

namespace Tests\Feature;

use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCountriesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_countries_endpoint_returns_all_configured_countries(): void
    {
        Country::create([
            'name' => 'Uganda',
            'currency_code' => 'UGX',
            'currency_name' => 'Uganda Shilling',
            'exchange_rate' => 1,
            'is_default' => true,
        ]);

        Country::create([
            'name' => 'Kenya',
            'currency_code' => 'KES',
            'currency_name' => 'Kenyan Shilling',
            'exchange_rate' => 1,
            'is_default' => false,
        ]);

        Country::create([
            'name' => 'Tanzania',
            'currency_code' => 'TZS',
            'currency_name' => 'Tanzanian Shilling',
            'exchange_rate' => 1,
            'is_default' => false,
        ]);

        $response = $this->getJson('/api/v1/countries');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $names = collect($response->json('data.countries'))->pluck('name')->all();

        $this->assertContains('Uganda', $names);
        $this->assertContains('Kenya', $names);
        $this->assertContains('Tanzania', $names);
        $this->assertCount(3, $names);
    }
}
