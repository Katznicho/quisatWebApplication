<?php

namespace Tests\Feature;

use App\Models\ParentGuardian;
use App\Services\ParentUniversalCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestParentUniversalAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_parent_register_creates_account_with_qsp_code(): void
    {
        $response = $this->postJson('/api/v1/auth/parent-register', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'Ada.Lovelace@Example.com',
            'phone' => '+256700111222',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'relationship' => 'mother',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.parent.account_type', 'guest')
            ->assertJsonPath('data.parent.business_id', null)
            ->assertJsonPath('data.parent.businesses', [])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'parent' => ['universal_code', 'universal_link', 'email'],
                ],
            ]);

        $code = $response->json('data.parent.universal_code');
        $this->assertNotEmpty($code);
        $this->assertStringStartsWith('QSP-', $code);
        $this->assertSame(12, strlen($code)); // QSP- + 8

        $this->assertDatabaseHas('parent_guardians', [
            'email' => 'ada.lovelace@example.com',
            'account_type' => 'guest',
            'business_id' => null,
            'universal_code' => $code,
        ]);
    }

    public function test_guest_register_duplicate_email_returns_account_exists(): void
    {
        ParentGuardian::factory()->create([
            'email' => 'taken@example.com',
            'phone' => '+256700999888',
        ]);

        $response = $this->postJson('/api/v1/auth/parent-register', [
            'first_name' => 'Other',
            'last_name' => 'Person',
            'email' => 'taken@example.com',
            'phone' => '+256700111333',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('code', 'ACCOUNT_EXISTS');
    }

    public function test_guest_register_duplicate_phone_returns_phone_in_use(): void
    {
        ParentGuardian::factory()->create([
            'email' => 'one@example.com',
            'phone' => '+256 700 111 222',
        ]);

        $response = $this->postJson('/api/v1/auth/parent-register', [
            'first_name' => 'Other',
            'last_name' => 'Person',
            'email' => 'two@example.com',
            'phone' => '256700111222',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('code', 'PHONE_IN_USE');
    }

    public function test_guest_login_succeeds_without_business(): void
    {
        $parent = ParentGuardian::factory()->create([
            'email' => 'guest@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/parent-login', [
            'email' => 'guest@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.parent.account_type', 'guest')
            ->assertJsonPath('data.parent.businesses', [])
            ->assertJsonPath('data.parent.id', $parent->id);
    }

    public function test_regenerate_invalidates_old_universal_code(): void
    {
        $codes = app(ParentUniversalCodeService::class);
        $parent = ParentGuardian::factory()->create();
        $old = $codes->ensureCode($parent);

        $token = $parent->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/parent/universal-code/regenerate');

        $response->assertOk();
        $new = $response->json('data.code');
        $this->assertNotSame($old, $new);
        $this->assertNull($codes->findByCode($old));
        $this->assertNotNull($codes->findByCode($new));
    }
}
