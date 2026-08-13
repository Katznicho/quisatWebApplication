<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Fee;
use App\Models\ParentGuardian;
use App\Models\PaymentCollection;
use App\Models\Student;
use App\Models\UserNotification;
use App\Services\FeeParentNotificationService;
use App\Services\MarzPayCheckoutService;
use App\Services\MarzPayPayableResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SchoolFeeMarzPayTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_list_pending_fees_for_their_child(): void
    {
        [$parent, $fee] = $this->seedFee();

        Sanctum::actingAs($parent);

        $response = $this->getJson('/api/v1/parent/fees');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.pending_count', 1)
            ->assertJsonPath('data.fees.0.uuid', $fee->uuid)
            ->assertJsonPath('data.fees.0.is_payable', true)
            ->assertJsonPath('data.fees.0.balance', 2000000);
    }

    public function test_parent_dashboard_includes_pending_fees(): void
    {
        [$parent, $fee] = $this->seedFee();

        Sanctum::actingAs($parent);

        $this->getJson('/api/v1/parent/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pending_fees.0.uuid', $fee->uuid);
    }

    public function test_other_parent_cannot_see_or_pay_the_fee(): void
    {
        [, $fee, $business] = $this->seedFee();
        $stranger = ParentGuardian::factory()->linked($business->id)->create();

        Sanctum::actingAs($stranger);

        $this->getJson('/api/v1/parent/fees')
            ->assertOk()
            ->assertJsonPath('data.summary.pending_count', 0);

        $this->postJson("/api/v1/parent/fees/{$fee->uuid}/pay", [
            'payment_method' => 'mtn_mobile_money',
            'phone_number' => '+256700000000',
        ])->assertNotFound();
    }

    public function test_resolver_treats_school_fee_as_marzpay_payable(): void
    {
        [, $fee, $business] = $this->seedFee();
        $resolver = app(MarzPayPayableResolver::class);

        $resolved = $resolver->resolve('school_fee', $fee->uuid);
        $this->assertTrue($fee->is($resolved));
        $this->assertSame('school_fee', $resolver->payableTypeKey($fee));
        $this->assertTrue($business->is($resolver->resolveBusiness($fee)));
        $this->assertSame(2000000, $resolver->amountFor($fee));
    }

    public function test_completed_payment_marks_fee_paid_and_notifies_parent(): void
    {
        [$parent, $fee] = $this->seedFee();

        $collection = new PaymentCollection([
            'reference' => 'FEE-TEST-REF',
            'status' => 'completed',
            'amount' => 2000000,
            'base_amount' => 2000000,
            'currency' => 'UGX',
            'method' => 'mobile_money',
            'provider' => 'marzpay',
        ]);

        $fee->markMarzPayCompleted($collection);
        $fee->refresh();

        $this->assertSame('paid', $fee->payment_status);
        $this->assertSame(0.0, (float) $fee->balance);
        $this->assertSame((float) $fee->amount, (float) $fee->amount_paid);
        $this->assertNotEmpty($fee->receipt_number);
        $this->assertDatabaseHas('user_notifications', [
            'notifiable_type' => ParentGuardian::class,
            'notifiable_id' => $parent->id,
            'title' => 'School fee paid',
        ]);
    }

    public function test_pay_endpoint_initiates_marzpay(): void
    {
        [$parent, $fee] = $this->seedFee();

        $this->mock(MarzPayCheckoutService::class, function ($mock) {
            $mock->shouldReceive('maybeInitiate')->once()->andReturn([
                'success' => true,
                'message' => 'Approve the prompt',
                'data' => [
                    'reference' => 'ref-1',
                    'amount' => 2000000,
                    'platform_charge' => 0,
                    'redirect_url' => null,
                ],
            ]);
            $mock->shouldReceive('registrationPaymentMeta')
                ->once()
                ->andReturn([
                    'payment_initiated' => true,
                    'payment_error' => null,
                    'payment' => [
                        'reference' => 'ref-1',
                        'amount' => 2000000,
                        'platform_charge' => 0,
                    ],
                    'message' => 'Approve the MarzPay prompt to complete this school fee.',
                ]);
        });

        Sanctum::actingAs($parent);

        $this->postJson("/api/v1/parent/fees/{$fee->uuid}/pay", [
            'payment_method' => 'mtn_mobile_money',
            'phone_number' => '+256700111222',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment_initiated', true)
            ->assertJsonPath('data.payment.reference', 'ref-1');
    }

    public function test_parent_can_record_partial_cash_payment(): void
    {
        [$parent, $fee] = $this->seedFee();

        Sanctum::actingAs($parent);

        $this->postJson("/api/v1/parent/fees/{$fee->uuid}/pay", [
            'payment_method' => 'cash',
            'amount' => 1000000,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.fee.payment_status', 'partial')
            ->assertJsonPath('data.fee.amount_paid', 1000000)
            ->assertJsonPath('data.fee.balance', 1000000);

        $this->assertDatabaseHas('fee_payments', [
            'fee_id' => $fee->id,
            'amount' => 1000000,
            'method' => 'cash',
        ]);
    }

    public function test_other_payment_requires_attached_proof(): void
    {
        [$parent, $fee] = $this->seedFee();

        Sanctum::actingAs($parent);

        $this->postJson("/api/v1/parent/fees/{$fee->uuid}/pay", [
            'payment_method' => 'other',
            'amount' => 500000,
        ])->assertStatus(422);
    }

    public function test_creating_fee_notifies_parent(): void
    {
        [$parent, $fee] = $this->seedFee();

        app(FeeParentNotificationService::class)->notifyCreated($fee);

        $this->assertDatabaseHas('user_notifications', [
            'notifiable_type' => ParentGuardian::class,
            'notifiable_id' => $parent->id,
            'title' => 'School fee pending',
        ]);
        $this->assertNotNull(UserNotification::query()->where('title', 'School fee pending')->first()?->data);
    }

    /**
     * @return array{0: ParentGuardian, 1: Fee, 2: Business, 3: Student}
     */
    protected function seedFee(): array
    {
        $business = Business::factory()->create();
        $parent = ParentGuardian::factory()->linked($business->id)->create([
            'phone' => '+256700111222',
        ]);

        $student = Student::create([
            'first_name' => 'Aleksei',
            'last_name' => 'Jeero',
            'email' => 'aleksei.'.Str::random(6).'@example.com',
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'gender' => 'male',
            'student_id' => 'STU-'.strtoupper(Str::random(6)),
            'admission_date' => now()->toDateString(),
            'business_id' => $business->id,
            'parent_guardian_id' => $parent->id,
            'status' => 'active',
        ]);

        $fee = Fee::create([
            'business_id' => $business->id,
            'student_id' => $student->id,
            'fee_type' => 'tuition',
            'amount' => 2000000,
            'amount_paid' => 0,
            'balance' => 2000000,
            'due_date' => now()->addDays(14)->toDateString(),
            'payment_status' => 'pending',
        ]);

        return [$parent, $fee->fresh(['student.parentGuardian', 'business']), $business, $student];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
