<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Currency;
use App\Models\EventAttendee;
use App\Models\ParentGuardian;
use App\Models\Program;
use App\Models\ProgramEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyProgrammesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_only_sees_programmes_they_registered_for(): void
    {
        $currency = Currency::create([
            'name' => 'Uganda Shilling',
            'code' => 'UGX',
            'symbol' => 'UGX',
            'exchange_rate' => 1,
        ]);
        $business = Business::factory()->create();
        $parent = ParentGuardian::factory()->linked($business->id)->create();
        $mappedUser = User::factory()->create([
            'email' => $parent->email,
            'business_id' => $business->id,
        ]);

        $subscribed = Program::create([
            'name' => 'Bible Adventure',
            'description' => 'Weekly kids programme',
            'status' => 'active',
        ]);
        $other = Program::create([
            'name' => 'Open House Club',
            'description' => 'Not registered',
            'status' => 'active',
        ]);

        $event = ProgramEvent::create([
            'program_ids' => [$subscribed->id],
            'name' => 'Creation Week',
            'description' => 'Learn about creation',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'price' => 0,
            'status' => 'open',
            'location' => 'Church Hall',
            'currency_id' => $currency->id,
            'business_id' => $business->id,
            'user_id' => $mappedUser->id,
        ]);

        ProgramEvent::create([
            'program_ids' => [$other->id],
            'name' => 'Open Session',
            'description' => 'Everyone welcome',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'price' => 0,
            'status' => 'open',
            'location' => 'Hall',
            'currency_id' => $currency->id,
            'business_id' => $business->id,
            'user_id' => $mappedUser->id,
        ]);

        EventAttendee::create([
            'uuid' => (string) Str::uuid(),
            'program_event_id' => $event->id,
            'user_id' => $mappedUser->id,
            'child_name' => 'Aisha',
            'child_age' => 7,
            'parent_name' => $parent->first_name.' '.$parent->last_name,
            'parent_phone' => $parent->phone,
            'parent_email' => $parent->email,
            'gender' => 'female',
            'payment_method' => 'cash',
            'amount_paid' => 0,
            'amount_due' => 0,
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($parent);

        $this->getJson('/api/v1/my-programmes')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.programs.0.name', 'Bible Adventure')
            ->assertJsonPath('data.programs.0.children.0', 'Aisha')
            ->assertJsonMissing(['name' => 'Open House Club']);
    }

    public function test_unauthenticated_my_programmes_is_rejected(): void
    {
        $this->getJson('/api/v1/my-programmes')
            ->assertUnauthorized();
    }
}
