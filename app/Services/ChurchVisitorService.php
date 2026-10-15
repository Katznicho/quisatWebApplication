<?php

namespace App\Services;

use App\Models\ChurchService;
use App\Models\ChurchVisitor;
use App\Models\ChurchVisitorAttendance;
use App\Models\ChurchVisitorRegistration;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\PickupCode;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChurchVisitorService
{
    public function register(ParentGuardian $parent, int $businessId, array $data): ChurchVisitorRegistration
    {
        return DB::transaction(function () use ($parent, $businessId, $data) {
            if (! empty($data['client_token'])) {
                $existing = ChurchVisitorRegistration::query()
                    ->where('client_token', $data['client_token'])
                    ->lockForUpdate()
                    ->first();
                if ($existing) {
                    if ((int) $existing->registered_by_parent_id !== (int) $parent->id) {
                        abort(403, 'This registration belongs to another family.');
                    }

                    return $existing->load('visitor.family', 'attendance.checkedInBy', 'classRoom');
                }
            }

            $service = $this->serviceFor($businessId, (int) ($data['church_service_id'] ?? 0), $data['service_date']);
            $visitor = $this->matchVisitor($parent, $businessId, $data) ?? ChurchVisitor::query()->create([
                'business_id' => $businessId,
                'family_id' => $parent->id,
                'registered_by_parent_id' => $parent->id,
                'full_name' => trim($data['full_name']),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'age' => $data['age'] ?? null,
                'relationship' => trim($data['relationship']),
                'care_notes' => $data['care_notes'] ?? null,
            ]);

            $activeKey = $visitor->id.'-'.($service?->id ?? 0).'-'.$data['service_date'];
            $duplicate = ChurchVisitorRegistration::query()->where('active_key', $activeKey)->lockForUpdate()->first();
            if ($duplicate) {
                return $duplicate->load('visitor.family', 'attendance.checkedInBy', 'classRoom');
            }

            $registration = ChurchVisitorRegistration::query()->create([
                'church_visitor_id' => $visitor->id,
                'business_id' => $businessId,
                'church_service_id' => $service?->id,
                'service_date' => $data['service_date'],
                'service_name' => $service?->name,
                'status' => 'awaiting',
                'registered_by_parent_id' => $parent->id,
                'pickup_code' => $this->reserveCode($businessId, $data['service_date']),
                'active_key' => $activeKey,
                'client_token' => $data['client_token'] ?? null,
            ]);

            return $registration->load('visitor.family', 'attendance.checkedInBy', 'classRoom');
        });
    }

    public function cancel(ParentGuardian $parent, ChurchVisitorRegistration $registration): ChurchVisitorRegistration
    {
        return DB::transaction(function () use ($parent, $registration) {
            $registration = ChurchVisitorRegistration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $this->assertFamily($parent, $registration);
            if ($registration->status !== 'awaiting') {
                throw ValidationException::withMessages([
                    'registration' => 'Only a registration that is still awaiting arrival can be cancelled.',
                ]);
            }

            $registration->update([
                'status' => 'cancelled',
                'active_key' => null,
                'cancelled_at' => now(),
                'cancelled_by_parent_id' => $parent->id,
            ]);

            return $registration->fresh()->load('visitor.family', 'attendance.checkedInBy', 'classRoom');
        });
    }

    public function remove(ParentGuardian $parent, ChurchVisitor $visitor): void
    {
        DB::transaction(function () use ($parent, $visitor) {
            $visitor = ChurchVisitor::query()->whereKey($visitor->id)->lockForUpdate()->firstOrFail();
            if ((int) $visitor->family_id !== (int) $parent->id) {
                abort(403, 'This visiting child belongs to another family.');
            }

            $inside = ChurchVisitorRegistration::query()
                ->where('church_visitor_id', $visitor->id)
                ->where('status', 'checked_in')
                ->lockForUpdate()
                ->exists();
            if ($inside) {
                throw ValidationException::withMessages([
                    'visitor' => 'This visiting child is still checked in and cannot be removed.',
                ]);
            }

            $visitor->update([
                'removed_at' => now(),
                'removed_by_parent_id' => $parent->id,
            ]);
        });
    }

    public function arrive(User $staff, ChurchVisitorRegistration $registration, int $classRoomId): ChurchVisitorRegistration
    {
        return DB::transaction(function () use ($staff, $registration, $classRoomId) {
            $registration = ChurchVisitorRegistration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            if ($registration->status !== 'awaiting') {
                throw ValidationException::withMessages([
                    'registration' => 'This visitor is not awaiting arrival.',
                ]);
            }

            $class = ClassRoom::query()
                ->where('business_id', $registration->business_id)
                ->whereKey($classRoomId)
                ->first();
            if (! $class) {
                throw ValidationException::withMessages(['class_room_id' => 'Choose a class at this church.']);
            }

            $registration->update([
                'status' => 'checked_in',
                'class_room_id' => $class->id,
            ]);

            ChurchVisitorAttendance::query()->create([
                'registration_id' => $registration->id,
                'business_id' => $registration->business_id,
                'church_visitor_id' => $registration->church_visitor_id,
                'class_room_id' => $class->id,
                'service_name' => $registration->service_name,
                'service_date' => $registration->service_date,
                'checked_in_at' => now(),
                'checked_in_by' => $staff->id,
            ]);

            return $registration->fresh()->load('visitor.family', 'visitor.registeredBy', 'attendance.checkedInBy', 'classRoom');
        });
    }

    public function checkout(User $staff, ChurchVisitorRegistration $registration, string $code, ?string $collector): ChurchVisitorRegistration
    {
        return DB::transaction(function () use ($staff, $registration, $code, $collector) {
            $registration = ChurchVisitorRegistration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();
            if ($registration->status !== 'checked_in') {
                throw ValidationException::withMessages([
                    'registration' => 'This visitor is not checked in.',
                ]);
            }

            $normalized = str_pad(preg_replace('/\D/', '', $code) ?: '', 4, '0', STR_PAD_LEFT);
            if ($normalized === '0000' || $normalized !== $registration->pickup_code || $registration->pickup_used_at) {
                throw ValidationException::withMessages([
                    'pickup_code' => 'That pickup code does not match this visiting child.',
                ]);
            }

            $registration->update([
                'status' => 'checked_out',
                'pickup_used_at' => now(),
            ]);
            $registration->attendance?->update([
                'checked_out_at' => now(),
                'checked_out_by' => $staff->id,
                'collector_name' => $collector ?: $registration->visitor?->family?->full_name,
            ]);

            return $registration->fresh()->load('visitor.family', 'visitor.registeredBy', 'attendance.checkedInBy', 'attendance.checkedOutBy', 'classRoom');
        });
    }

    public function payload(ChurchVisitorRegistration $registration, bool $includeCareNotes): array
    {
        $registration->loadMissing('visitor.family', 'visitor.registeredBy', 'attendance.checkedInBy', 'attendance.checkedOutBy', 'classRoom');
        $visitor = $registration->visitor;
        $family = $visitor?->family;
        $attendance = $registration->attendance;

        return [
            'id' => $registration->id,
            'visitor_id' => $visitor?->id,
            'full_name' => $visitor?->full_name,
            'age_label' => $visitor?->ageLabel(),
            'relationship' => $visitor?->relationship,
            'care_notes' => $includeCareNotes ? $visitor?->care_notes : null,
            'family_id' => $visitor?->family_id,
            'family_name' => $family?->full_name,
            'with_label' => 'With '.($family?->full_name ?: 'family'),
            'service_name' => $registration->service_name,
            'service_date' => optional($registration->service_date)->toDateString(),
            'status' => $registration->status,
            'status_label' => $registration->statusLabel(),
            'class_name' => $registration->classRoom?->name,
            'pickup_code' => $registration->pickup_code,
            'registered_by' => $visitor?->registeredBy?->full_name,
            'arrival_confirmed_by' => $attendance?->checkedInBy?->name,
            'checked_in_at' => optional($attendance?->checked_in_at)->toIso8601String(),
            'checked_out_at' => optional($attendance?->checked_out_at)->toIso8601String(),
            'collector_name' => $attendance?->collector_name,
            'can_delete' => $visitor && ! $visitor->removed_at && $registration->status !== 'checked_in',
            'can_cancel' => $registration->status === 'awaiting',
        ];
    }

    private function matchVisitor(ParentGuardian $parent, int $businessId, array $data): ?ChurchVisitor
    {
        $name = mb_strtolower(trim($data['full_name']));

        return ChurchVisitor::query()
            ->where('business_id', $businessId)
            ->where('family_id', $parent->id)
            ->whereNull('removed_at')
            ->get()
            ->first(function (ChurchVisitor $visitor) use ($name, $data) {
                if (mb_strtolower($visitor->full_name) !== $name) {
                    return false;
                }
                if (! empty($data['date_of_birth']) && $visitor->date_of_birth) {
                    return $visitor->date_of_birth->toDateString() === $data['date_of_birth'];
                }

                return empty($data['age']) || (int) $visitor->age === (int) $data['age'];
            });
    }

    private function serviceFor(int $businessId, int $serviceId, string $date): ?ChurchService
    {
        $configured = ChurchService::query()->where('business_id', $businessId)->exists();
        if (! $configured) {
            throw ValidationException::withMessages([
                'church_service_id' => 'This church has no services yet.',
            ]);
        }

        $service = ChurchService::query()->where('business_id', $businessId)->whereKey($serviceId)->first();
        if (! $service || ! $service->is_active || ! $service->occursOn($date)) {
            throw ValidationException::withMessages([
                'church_service_id' => 'Select an active service scheduled for this date.',
            ]);
        }

        return $service;
    }

    private function assertFamily(ParentGuardian $parent, ChurchVisitorRegistration $registration): void
    {
        if ((int) $registration->visitor?->family_id !== (int) $parent->id) {
            abort(403, 'This visiting child belongs to another family.');
        }
    }

    private function reserveCode(int $businessId, string $date): string
    {
        do {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (
            $code === '0000'
            || PickupCode::query()->where('business_id', $businessId)->whereDate('code_date', $date)->where('code', $code)->exists()
            || ChurchVisitorRegistration::query()->where('business_id', $businessId)->whereDate('service_date', $date)->where('pickup_code', $code)->where('status', '!=', 'cancelled')->exists()
        );

        return $code;
    }
}
