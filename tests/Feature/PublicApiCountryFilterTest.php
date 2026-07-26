<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Feature;
use App\Models\ParentGuardian;
use App\Models\Product;
use App\Models\Program;
use App\Models\ProgramEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicApiCountryFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Country $uganda;

    protected Country $kenya;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uganda = Country::create([
            'name' => 'Uganda',
            'currency_code' => 'UGX',
            'currency_name' => 'Uganda Shilling',
            'exchange_rate' => 1,
            'is_default' => true,
        ]);

        $this->kenya = Country::create([
            'name' => 'Kenya',
            'currency_code' => 'KES',
            'currency_name' => 'Kenyan Shilling',
            'exchange_rate' => 1,
            'is_default' => false,
        ]);
    }

    public function test_clinic_list_defaults_to_default_country_for_guests(): void
    {
        $clinicFeature = Feature::create([
            'name' => 'Kids Clinics',
            'description' => 'Clinics',
            'price' => 0,
        ]);

        $ugandaClinic = Business::factory()->create([
            'name' => 'Uganda Clinic',
            'country_id' => $this->uganda->id,
            'country' => 'Uganda',
            'status' => 'active',
            'enabled_feature_ids' => [$clinicFeature->id],
        ]);

        $kenyaClinic = Business::factory()->create([
            'name' => 'Kenya Clinic',
            'country_id' => $this->kenya->id,
            'country' => 'Kenya',
            'status' => 'active',
            'enabled_feature_ids' => [$clinicFeature->id],
        ]);

        $response = $this->getJson('/api/v1/clinics');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $names = collect($response->json('data.clinics'))->pluck('name')->all();

        $this->assertContains('Uganda Clinic', $names);
        $this->assertNotContains('Kenya Clinic', $names);
    }

    public function test_clinic_list_uses_authenticated_staff_business_country(): void
    {
        $clinicFeature = Feature::create([
            'name' => 'Kids Clinics',
            'description' => 'Clinics',
            'price' => 0,
        ]);

        $kenyaBusiness = Business::factory()->create([
            'name' => 'Kenya School',
            'country_id' => $this->kenya->id,
            'country' => 'Kenya',
            'status' => 'active',
        ]);

        $staff = User::factory()->create([
            'business_id' => $kenyaBusiness->id,
            'status' => 'active',
        ]);

        Business::factory()->create([
            'name' => 'Uganda Clinic',
            'country_id' => $this->uganda->id,
            'country' => 'Uganda',
            'status' => 'active',
            'enabled_feature_ids' => [$clinicFeature->id],
        ]);

        Business::factory()->create([
            'name' => 'Kenya Clinic',
            'country_id' => $this->kenya->id,
            'country' => 'Kenya',
            'status' => 'active',
            'enabled_feature_ids' => [$clinicFeature->id],
        ]);

        Sanctum::actingAs($staff);

        $response = $this->getJson('/api/v1/clinics');

        $names = collect($response->json('data.clinics'))->pluck('name')->all();

        $this->assertContains('Kenya Clinic', $names);
        $this->assertNotContains('Uganda Clinic', $names);
    }

    public function test_product_list_can_be_filtered_by_country_query_param(): void
    {
        $ugandaBusiness = Business::factory()->create([
            'country_id' => $this->uganda->id,
            'country' => 'Uganda',
        ]);

        $kenyaBusiness = Business::factory()->create([
            'country_id' => $this->kenya->id,
            'country' => 'Kenya',
        ]);

        Product::create([
            'business_id' => $ugandaBusiness->id,
            'name' => 'Uganda Toy',
            'hub' => 'kidz_mart',
            'status' => 'active',
            'is_available' => true,
            'price' => 1000,
        ]);

        Product::create([
            'business_id' => $kenyaBusiness->id,
            'name' => 'Kenya Toy',
            'hub' => 'kidz_mart',
            'status' => 'active',
            'is_available' => true,
            'price' => 1000,
        ]);

        $response = $this->getJson('/api/v1/products?country_id='.$this->kenya->id);

        $names = collect($response->json('data.products'))->pluck('name')->all();

        $this->assertContains('Kenya Toy', $names);
        $this->assertNotContains('Uganda Toy', $names);
    }

    public function test_parent_cannot_self_join_clinic_in_another_country(): void
    {
        $clinicFeature = Feature::create([
            'name' => 'Kids Clinics',
            'description' => 'Clinics',
            'price' => 0,
        ]);

        $kenyaBusiness = Business::factory()->create([
            'country_id' => $this->kenya->id,
            'country' => 'Kenya',
        ]);

        $parent = ParentGuardian::factory()->create([
            'business_id' => $kenyaBusiness->id,
            'account_type' => 'linked',
            'status' => 'active',
        ]);

        $ugandaClinic = Business::factory()->create([
            'country_id' => $this->uganda->id,
            'country' => 'Uganda',
            'status' => 'active',
            'enabled_feature_ids' => [$clinicFeature->id],
        ]);

        Sanctum::actingAs($parent);

        $response = $this->postJson('/api/v1/parent/join-business', [
            'business_id' => $ugandaClinic->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'COUNTRY_MISMATCH');
    }

    public function test_christian_kids_hub_programs_show_for_uganda_not_kenya(): void
    {
        $currency = Currency::create([
            'name' => 'Uganda Shilling',
            'code' => 'UGX',
            'symbol' => 'UGX',
            'exchange_rate' => 1,
        ]);

        $program = Program::create([
            'name' => 'Bible Adventure',
            'description' => 'Faith-based kids program',
            'status' => 'active',
        ]);

        $kenyaBusiness = Business::factory()->create([
            'country_id' => $this->kenya->id,
            'country' => 'Kenya',
        ]);

        ProgramEvent::create([
            'program_ids' => [$program->id],
            'name' => 'Creation Week',
            'description' => 'Learn about creation',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'price' => 0,
            'status' => 'open',
            'location' => 'Church Hall',
            'currency_id' => $currency->id,
            'business_id' => $kenyaBusiness->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $ugandaResponse = $this->getJson('/api/v1/programmes?country_id='.$this->uganda->id);
        $kenyaResponse = $this->getJson('/api/v1/programmes?country_id='.$this->kenya->id);

        $ugandaNames = collect($ugandaResponse->json('data.programs'))->pluck('name')->all();
        $kenyaNames = collect($kenyaResponse->json('data.programs'))->pluck('name')->all();

        $this->assertContains('Bible Adventure', $ugandaNames);
        $this->assertNotContains('Bible Adventure', $kenyaNames);
    }
}
