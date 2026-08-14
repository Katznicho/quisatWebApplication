<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ClinicPatient;
use App\Models\Fee;
use App\Models\ParentGuardian;
use App\Services\FeeParentNotificationService;
use App\Services\MarzPayPayableResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClinicFeeBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_list_clinic_fees_for_their_patient(): void
    {
        [$parent, $fee] = $this->seedClinicFee();

        Sanctum::actingAs($parent);

        $this->getJson('/api/v1/parent/fees')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.pending_count', 1)
            ->assertJsonPath('data.fees.0.uuid', $fee->uuid)
            ->assertJsonPath('data.fees.0.billing_context', 'clinic')
            ->assertJsonPath('data.fees.0.clinic_patient.patient_number', $fee->clinicPatient->patient_number);
    }

    public function test_parent_dashboard_includes_clinic_pending_fees(): void
    {
        [$parent, $fee] = $this->seedClinicFee();

        Sanctum::actingAs($parent);

        $this->getJson('/api/v1/parent/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pending_fees.0.uuid', $fee->uuid)
            ->assertJsonPath('data.pending_fees.0.billing_context', 'clinic');
    }

    public function test_resolver_treats_clinic_fee_as_marzpay_payable(): void
    {
        [, $fee, $business] = $this->seedClinicFee();
        $resolver = app(MarzPayPayableResolver::class);

        $resolved = $resolver->resolve('clinic_fee', $fee->uuid);
        $this->assertTrue($fee->is($resolved));
        $this->assertSame('clinic_fee', $resolver->payableTypeKey($fee));
        $this->assertTrue($business->is($resolver->resolveBusiness($fee)));
        $this->assertSame(150000, $resolver->amountFor($fee));
        $this->assertStringContainsString('Clinic fee', $resolver->descriptionFor($fee));
    }

    public function test_creating_clinic_fee_notifies_parent(): void
    {
        [$parent, $fee] = $this->seedClinicFee();

        app(FeeParentNotificationService::class)->notifyCreated($fee);

        $this->assertDatabaseHas('user_notifications', [
            'notifiable_type' => ParentGuardian::class,
            'notifiable_id' => $parent->id,
            'title' => 'Clinic fee pending',
        ]);
    }

    public function test_parent_can_record_cash_payment_on_clinic_fee(): void
    {
        [$parent, $fee] = $this->seedClinicFee();

        Sanctum::actingAs($parent);

        $this->postJson("/api/v1/parent/fees/{$fee->uuid}/pay", [
            'payment_method' => 'cash',
            'amount' => 50000,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.fee.payment_status', 'partial')
            ->assertJsonPath('data.fee.amount_paid', 50000)
            ->assertJsonPath('data.fee.balance', 100000);
    }

    /**
     * @return array{0: ParentGuardian, 1: Fee, 2: Business, 3: ClinicPatient}
     */
    protected function seedClinicFee(): array
    {
        $business = Business::factory()->create();
        $parent = ParentGuardian::factory()->linked($business->id)->create([
            'phone' => '+256700333444',
        ]);

        $patient = ClinicPatient::create([
            'business_id' => $business->id,
            'parent_guardian_id' => $parent->id,
            'first_name' => 'Nora',
            'last_name' => 'Clinic',
            'date_of_birth' => now()->subYears(5)->toDateString(),
            'gender' => 'female',
            'status' => 'active',
        ]);

        $fee = Fee::create([
            'business_id' => $business->id,
            'clinic_patient_id' => $patient->id,
            'student_id' => null,
            'fee_type' => 'Consultation',
            'amount' => 150000,
            'amount_paid' => 0,
            'balance' => 150000,
            'due_date' => now()->addDays(7)->toDateString(),
            'payment_status' => 'pending',
        ]);

        return [$parent, $fee->fresh(['clinicPatient.parentGuardian', 'business']), $business, $patient];
    }
}
