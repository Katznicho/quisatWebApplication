<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ClinicAppointment;
use App\Models\ClinicDoctor;
use App\Models\ClinicFamily;
use App\Models\ClinicPatient;
use App\Models\ClinicService;
use App\Models\Currency;
use App\Models\Feature;
use App\Models\ParentGuardian;
use App\Models\PaymentCollection;
use App\Models\User;
use App\Services\MarzPayCheckoutService;
use App\Services\MarzPayPayableResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class ClinicAppointmentMarzPayTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_resolves_clinic_appointment_and_business(): void
    {
        [$clinic, $appointment] = $this->seedClinicAppointment([
            'amount' => 15000,
            'payment_status' => 'pending',
            'payment_method' => 'mtn_mobile_money',
        ]);

        $resolver = app(MarzPayPayableResolver::class);

        $resolved = $resolver->resolve('clinic_appointment', (string) $appointment->id);
        $this->assertTrue($appointment->is($resolved));
        $this->assertSame('clinic_appointment', $resolver->payableTypeKey($appointment));
        $this->assertTrue($clinic->is($resolver->resolveBusiness($appointment)));
        $this->assertSame(15000, $resolver->amountFor($appointment));
    }

    public function test_appointment_marz_pay_transitions_use_snapshot_amount(): void
    {
        [, $appointment] = $this->seedClinicAppointment([
            'amount' => 12000,
            'payment_status' => 'pending',
            'payment_method' => 'airtel_money',
        ]);

        $this->assertSame(12000, $appointment->marzPayAmount());
        $this->assertStringContainsString('Clinic appointment:', $appointment->marzPayDescription());
        $this->assertNotEmpty($appointment->marzPayPhoneNumber());

        $collection = new PaymentCollection([
            'uuid' => (string) Str::uuid(),
            'reference' => 'TEST-REF',
            'status' => 'completed',
            'amount' => 12000,
            'base_amount' => 12000,
            'currency' => 'UGX',
            'method' => 'mobile_money',
            'provider' => 'marzpay',
        ]);

        $appointment->markMarzPayCompleted($collection);
        $appointment->refresh();
        $this->assertSame('paid', $appointment->payment_status);
        $this->assertNotNull($appointment->paid_at);

        $appointment->update(['payment_status' => 'pending', 'paid_at' => null]);
        $appointment->markMarzPayFailed($collection);
        $this->assertSame('failed', $appointment->fresh()->payment_status);
    }

    public function test_book_free_service_sets_not_required_without_marzpay(): void
    {
        [$clinic, $parent, $patient, $doctor] = $this->seedClinicBookingContext();

        ClinicService::create([
            'business_id' => $clinic->id,
            'name' => 'Free checkup',
            'price' => 0,
            'status' => 'active',
        ]);

        $this->mock(MarzPayCheckoutService::class, function ($mock) {
            $mock->shouldReceive('maybeInitiate')->once()->andReturn(null);
            $mock->shouldReceive('registrationPaymentMeta')
                ->once()
                ->andReturn([
                    'payment_initiated' => false,
                    'payment_error' => null,
                    'payment' => null,
                    'message' => 'Appointment request booked successfully.',
                ]);
        });

        Sanctum::actingAs($parent);

        $response = $this->postJson(
            "/api/v1/parent/clinics/{$clinic->id}/patients/{$patient->id}/appointments",
            [
                'doctor_name' => $doctor->name,
                'appointment_type' => 'Free checkup',
                'scheduled_at' => now()->addDay()->toIso8601String(),
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment_initiated', false)
            ->assertJsonPath('data.appointment.payment_status', 'not_required');

        $this->assertDatabaseHas('clinic_appointments', [
            'clinic_patient_id' => $patient->id,
            'payment_status' => 'not_required',
            'amount' => null,
        ]);
    }

    public function test_book_paid_service_snapshots_amount_and_ignores_client_amount(): void
    {
        [$clinic, $parent, $patient, $doctor] = $this->seedClinicBookingContext();

        $service = ClinicService::create([
            'business_id' => $clinic->id,
            'name' => 'General checkup',
            'price' => 25000,
            'status' => 'active',
        ]);

        $this->mock(MarzPayCheckoutService::class, function ($mock) {
            $mock->shouldReceive('maybeInitiate')->once()->andReturn(null);
            $mock->shouldReceive('registrationPaymentMeta')
                ->once()
                ->andReturn([
                    'payment_initiated' => false,
                    'payment_error' => null,
                    'payment' => null,
                    'message' => 'Appointment request booked successfully.',
                ]);
        });

        Sanctum::actingAs($parent);

        $response = $this->postJson(
            "/api/v1/parent/clinics/{$clinic->id}/patients/{$patient->id}/appointments",
            [
                'doctor_name' => $doctor->name,
                'clinic_service_id' => $service->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
                'payment_method' => 'cash',
                'amount' => 1,
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('data.appointment.payment_status', 'pending')
            ->assertJsonPath('data.appointment.amount', 25000)
            ->assertJsonPath('data.appointment.clinic_service_id', $service->id)
            ->assertJsonPath('data.appointment.payment_method', 'cash');

        $this->assertDatabaseHas('clinic_appointments', [
            'clinic_service_id' => $service->id,
            'amount' => 25000,
            'payment_status' => 'pending',
            'payment_method' => 'cash',
        ]);
    }

    public function test_paid_service_requires_payment_method(): void
    {
        [$clinic, $parent, $patient, $doctor] = $this->seedClinicBookingContext();

        $service = ClinicService::create([
            'business_id' => $clinic->id,
            'name' => 'Paid consult',
            'price' => 10000,
            'status' => 'active',
        ]);

        Sanctum::actingAs($parent);

        $response = $this->postJson(
            "/api/v1/parent/clinics/{$clinic->id}/patients/{$patient->id}/appointments",
            [
                'doctor_name' => $doctor->name,
                'clinic_service_id' => $service->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_online_initiate_error_keeps_pending_booking(): void
    {
        [$clinic, $parent, $patient, $doctor] = $this->seedClinicBookingContext();

        $service = ClinicService::create([
            'business_id' => $clinic->id,
            'name' => 'Online consult',
            'price' => 20000,
            'status' => 'active',
        ]);

        $this->mock(MarzPayCheckoutService::class, function ($mock) {
            $mock->shouldReceive('maybeInitiate')->once()->andReturn([
                'success' => false,
                'message' => 'Phone number is required for mobile money payments.',
            ]);
            $mock->shouldReceive('registrationPaymentMeta')
                ->once()
                ->andReturn([
                    'payment_initiated' => false,
                    'payment_error' => 'Phone number is required for mobile money payments.',
                    'payment' => null,
                    'message' => 'Registration saved, but online payment could not be started.',
                ]);
        });

        Sanctum::actingAs($parent);

        $response = $this->postJson(
            "/api/v1/parent/clinics/{$clinic->id}/patients/{$patient->id}/appointments",
            [
                'doctor_name' => $doctor->name,
                'clinic_service_id' => $service->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
                'payment_method' => 'mtn_mobile_money',
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('payment_initiated', false)
            ->assertJsonPath('payment_error', 'Phone number is required for mobile money payments.')
            ->assertJsonPath('data.appointment.payment_status', 'pending');

        $this->assertDatabaseHas('clinic_appointments', [
            'clinic_service_id' => $service->id,
            'payment_status' => 'pending',
            'amount' => 20000,
        ]);
    }

    public function test_staff_mark_paid_and_waive_update_status_without_wallet_side_effects(): void
    {
        [$clinic, $appointment] = $this->seedClinicAppointment([
            'amount' => 18000,
            'payment_status' => 'pending',
            'payment_method' => 'cash',
        ]);

        $staff = User::factory()->create([
            'business_id' => $clinic->id,
        ]);

        $this->actingAs($staff)
            ->post(route('clinic-appointments.mark-paid', $appointment), [
                'notes' => 'Cash received at desk',
            ])
            ->assertRedirect();

        $appointment->refresh();
        $this->assertSame('paid', $appointment->payment_status);
        $this->assertNotNull($appointment->paid_at);
        $this->assertSame('Cash received at desk', $appointment->payment_notes);
        $this->assertDatabaseCount('payment_collections', 0);

        $appointment->update(['payment_status' => 'pending', 'paid_at' => null]);

        $this->actingAs($staff)
            ->post(route('clinic-appointments.waive', $appointment), [
                'notes' => 'Staff courtesy waive',
            ])
            ->assertRedirect();

        $appointment->refresh();
        $this->assertSame('waived', $appointment->payment_status);
        $this->assertNull($appointment->paid_at);
        $this->assertSame('Staff courtesy waive', $appointment->payment_notes);
    }

    /**
     * @return array{0: Business, 1: ClinicAppointment}
     */
    protected function seedClinicAppointment(array $appointmentOverrides = []): array
    {
        [$clinic, , $patient] = $this->seedClinicBookingContext();

        $appointment = ClinicAppointment::create(array_merge([
            'business_id' => $clinic->id,
            'clinic_patient_id' => $patient->id,
            'scheduled_at' => now()->addDay(),
            'doctor_name' => 'Dr Test',
            'appointment_type' => 'Consultation',
            'status' => 'scheduled',
            'currency' => 'UGX',
            'payment_status' => 'not_required',
        ], $appointmentOverrides));

        return [$clinic, $appointment->fresh(['patient.parentGuardian'])];
    }

    /**
     * @return array{0: Business, 1: ParentGuardian, 2: ClinicPatient, 3: ClinicDoctor}
     */
    protected function seedClinicBookingContext(): array
    {
        $currency = Currency::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Ugandan Shilling',
            'code' => 'UGX',
            'symbol' => 'USh',
            'rate' => '1',
            'status' => 'active',
            'is_default' => true,
        ]);

        $feature = Feature::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Kids Clinics',
            'description' => 'Clinic feature',
            'currency_id' => $currency->id,
            'price' => '0',
        ]);

        $clinic = Business::factory()->create([
            'enabled_feature_ids' => [$feature->id],
        ]);

        $parent = ParentGuardian::factory()->create([
            'phone' => '+256700111222',
        ]);

        $family = ClinicFamily::create([
            'business_id' => $clinic->id,
            'access_code' => ClinicFamily::generateUniqueAccessCode($clinic->id),
            'family_name' => 'Test Family',
            'primary_parent_guardian_id' => $parent->id,
            'status' => 'active',
        ]);

        $patient = ClinicPatient::create([
            'business_id' => $clinic->id,
            'clinic_family_id' => $family->id,
            'parent_guardian_id' => $parent->id,
            'first_name' => 'Child',
            'last_name' => 'One',
            'status' => 'active',
        ]);

        $doctor = ClinicDoctor::create([
            'business_id' => $clinic->id,
            'name' => 'Dr Amina',
            'status' => 'active',
        ]);

        return [$clinic, $parent, $patient, $doctor];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
