<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\ParentGuardian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_counts_platform_businesses_and_excludes_system_record(): void
    {
        $schoolCategory = BusinessCategory::factory()->create(['name' => 'School']);
        $clinicCategory = BusinessCategory::factory()->create(['name' => 'Clinic']);

        $systemBusiness = Business::factory()->create([
            'name' => 'System Administration',
            'registration_verified_at' => now(),
        ]);

        Business::factory()->create([
            'name' => 'Verified School',
            'business_category_id' => $schoolCategory->id,
            'registration_verified_at' => now(),
        ]);

        Business::factory()->create([
            'name' => 'Pending School',
            'business_category_id' => $schoolCategory->id,
            'registration_verified_at' => null,
        ]);

        Business::factory()->create([
            'name' => 'Pending Clinic',
            'business_category_id' => $clinicCategory->id,
            'registration_verified_at' => null,
        ]);

        ParentGuardian::factory()->count(2)->create();

        $this->assertSame(1, $systemBusiness->id);

        $response = $this->getJson('/api/v1/stats');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.businesses', 3)
            ->assertJsonPath('data.schools', 2)
            ->assertJsonPath('data.parents', 2)
            ->assertJsonMissingPath('data.pending_businesses')
            ->assertJsonMissingPath('data.verified_businesses');
    }
}
