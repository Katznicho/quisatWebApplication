<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ParentGuardian;
use App\Models\ParentGuardianBusiness;
use App\Models\PrayerRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrayerRequestVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_prayer_request_is_visible_to_church_admin_even_if_stored_on_another_business(): void
    {
        $school = Business::factory()->create(['type' => 'school']);
        $church = Business::factory()->create(['type' => 'church']);

        $parent = ParentGuardian::factory()->linked($school->id)->create();
        ParentGuardianBusiness::create([
            'parent_guardian_id' => $parent->id,
            'business_id' => $church->id,
            'status' => 'active',
            'joined_via' => 'staff',
            'joined_at' => now(),
        ]);

        $request = PrayerRequest::create([
            'business_id' => $school->id,
            'parent_guardian_id' => $parent->id,
            'body' => 'Pray for protection',
            'is_anonymous' => false,
            'status' => 'received',
        ]);

        $visible = PrayerRequest::query()->forBusiness($church->id)->pluck('id');

        $this->assertTrue($visible->contains($request->id));
    }

    public function test_parent_prayer_from_app_is_stored_on_their_church(): void
    {
        $school = Business::factory()->create(['type' => 'school']);
        $church = Business::factory()->create(['type' => 'church']);

        $parent = ParentGuardian::factory()->linked($school->id)->create();
        ParentGuardianBusiness::create([
            'parent_guardian_id' => $parent->id,
            'business_id' => $church->id,
            'status' => 'active',
            'joined_via' => 'staff',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($parent);

        $this->postJson('/api/v1/prayer-requests', [
            'body' => 'Pay for me get money',
            'is_anonymous' => false,
        ])->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('prayer_requests', [
            'parent_guardian_id' => $parent->id,
            'business_id' => $church->id,
            'body' => 'Pay for me get money',
        ]);

        $staff = User::factory()->create(['business_id' => $church->id]);
        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/prayer-requests')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['body' => 'Pay for me get money']);
    }
}
