<?php

namespace Tests\Feature;

use App\Models\ParentGuardian;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\BusinessAccountStatementService;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserNotificationDedupTest extends TestCase
{
    use RefreshDatabase;

    public function test_linked_parent_and_user_notifications_are_returned_once(): void
    {
        $email = 'parent.dup@example.com';
        $parent = ParentGuardian::factory()->create(['email' => $email]);
        $user = User::factory()->create(['email' => $email]);

        UserNotification::create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'title' => 'New message',
            'body' => 'Hello from teacher',
            'data' => ['type' => 'message', 'message_id' => '99'],
        ]);

        UserNotification::create([
            'notifiable_type' => ParentGuardian::class,
            'notifiable_id' => $parent->id,
            'title' => 'New message',
            'body' => 'Hello from teacher',
            'data' => ['type' => 'message', 'message_id' => '99'],
        ]);

        Sanctum::actingAs($parent);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.unread_count', 1);

        $this->assertCount(1, $response->json('data.data'));
        $this->assertSame('New message', $response->json('data.data.0.title'));
    }

    public function test_account_statement_displays_ksh_for_kenya_codes(): void
    {
        $business = Business::factory()->create(['currency_code' => 'KHS']);
        $statement = app(BusinessAccountStatementService::class)->build(
            $business,
            now()->subDays(30),
            now()
        );

        $this->assertSame('KSH', $statement['currency']);

        $business->update(['currency_code' => 'KES']);
        $statement = app(BusinessAccountStatementService::class)->build(
            $business->fresh(),
            now()->subDays(30),
            now()
        );

        $this->assertSame('KSH', $statement['currency']);
    }
}
