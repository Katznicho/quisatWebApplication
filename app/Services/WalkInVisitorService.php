<?php

namespace App\Services;

use App\Models\ChurchService;
use App\Models\ChurchVisitorRegistration;
use App\Models\ClassRoom;
use App\Models\PickupCode;
use App\Models\User;
use App\Models\WalkinCheckin;
use App\Models\WalkinChild;
use App\Models\WalkinFamily;
use App\Models\WalkinVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalkInVisitorService
{
    public function __construct(private TeacherClassScope $classes) {}

    public function lookup(int $businessId, string $phone): array
    {
        $digits = $this->digits($phone);
        if (strlen($digits) < 7) {
            return [];
        }

        return WalkinFamily::query()
            ->with('children')
            ->where('business_id', $businessId)
            ->where('phone_digits', $digits)
            ->orderBy('guardian_name')
            ->get()
            ->map(fn (WalkinFamily $family) => [
                'id' => $family->id,
                'guardian_name' => $family->guardian_name,
                'phone' => $family->phone,
                'relationship' => $family->relationship,
                'collector_name' => $family->collector_name,
                'children' => $family->children->map(fn (WalkinChild $child) => [
                    'id' => $child->id,
                    'full_name' => $child->full_name,
                    'age_label' => $child->ageLabel(),
                    'date_of_birth' => optional($child->date_of_birth)->toDateString(),
                    'age' => $child->age,
                    'care_notes' => $child->care_notes,
                ])->values(),
            ])
            ->all();
    }

    public function register(User $staff, int $businessId, array $data): WalkinCheckin
    {
        return DB::transaction(function () use ($staff, $businessId, $data) {
            $existing = WalkinCheckin::query()->where('client_token', $data['client_token'])->lockForUpdate()->first();
            if ($existing) {
                if ((int) $existing->business_id !== $businessId) {
                    abort(403);
                }

                return $existing->load('visits.child', 'visits.classRoom', 'family');
            }

            if (empty($data['participation_consent'])) {
                throw ValidationException::withMessages([
                    'participation_consent' => 'Participation consent is required before check-in.',
                ]);
            }

            $service = $this->serviceFor($businessId, (int) $data['church_service_id'], $data['service_date']);
            $family = $this->familyFor($staff, $businessId, $data);
            $checkin = WalkinCheckin::query()->create([
                'business_id' => $businessId,
                'walkin_family_id' => $family->id,
                'church_service_id' => $service->id,
                'service_date' => $data['service_date'],
                'client_token' => $data['client_token'],
                'created_by' => $staff->id,
            ]);

            $visitIds = [];
            foreach ($data['children'] as $childData) {
                $child = $this->childFor($family, $childData);
                $activeKey = $child->id.'-'.$service->id.'-'.$data['service_date'];
                $duplicate = WalkinVisit::query()->where('active_key', $activeKey)->lockForUpdate()->first();
                if ($duplicate) {
                    $visitIds[] = $duplicate->id;
                    continue;
                }

                $visit = WalkinVisit::query()->create([
                    'walkin_checkin_id' => $checkin->id,
                    'walkin_child_id' => $child->id,
                    'walkin_family_id' => $family->id,
                    'business_id' => $businessId,
                    'church_service_id' => $service->id,
                    'service_date' => $data['service_date'],
                    'service_name' => $service->name,
                    'class_room_id' => $this->classFor($staff, $businessId, (int) $childData['class_room_id'])->id,
                    'status' => 'checked_in',
                    'pickup_code' => $this->reserveCode($businessId, $data['service_date']),
                    'checked_in_at' => now(),
                    'checked_in_by' => $staff->id,
                    'collector_name' => $family->collector_name,
                    'active_key' => $activeKey,
                ]);
                $visitIds[] = $visit->id;
            }

            $checkin->setRelation('family', $family);
            $checkin->setRelation('visits', WalkinVisit::query()->whereIn('id', $visitIds)->with('child', 'classRoom', 'checkedInBy')->get());

            return $checkin;
        });
    }

    public function checkout(User $staff, int $businessId, array $data): array
    {
        return DB::transaction(function () use ($staff, $businessId, $data) {
            $visits = WalkinVisit::query()
                ->where('business_id', $businessId)
                ->whereIn('id', $data['visit_ids'])
                ->lockForUpdate()
                ->get();

            if ($visits->count() !== count($data['visit_ids'])) {
                abort(404);
            }

            $familyIds = $visits->pluck('walkin_family_id')->unique();
            if ($familyIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'visit_ids' => 'Collect children from one family at a time.',
                ]);
            }

            $exception = trim((string) ($data['exception_reason'] ?? ''));
            if ($exception !== '') {
                if (! $staff->isAdmin() && ! $staff->isBusinessAdmin()) {
                    abort(403, 'Only a church administrator can approve collection without a pickup code.');
                }
            } else {
                $code = $this->normalizeCode((string) ($data['pickup_code'] ?? ''));
                $open = $visits->where('status', 'checked_in');
                $unmatched = $open->contains(fn (WalkinVisit $visit) => $visit->pickup_code !== $code);
                if ($code === '0000' || $open->isEmpty() || $unmatched) {
                    throw ValidationException::withMessages([
                        'pickup_code' => 'That pickup code does not match every selected child.',
                    ]);
                }
            }

            if (empty($data['collector_confirmed'])) {
                throw ValidationException::withMessages([
                    'collector_confirmed' => 'Confirm the authorised collector before releasing a child.',
                ]);
            }

            foreach ($visits as $visit) {
                if ($visit->status === 'checked_out') {
                    continue;
                }
                $visit->update([
                    'status' => 'checked_out',
                    'checked_out_at' => now(),
                    'checked_out_by' => $staff->id,
                    'collector_name' => $data['collector_name'] ?: $visit->collector_name,
                    'exception_reason' => $exception !== '' ? $exception : null,
                ]);
            }

            return WalkinVisit::query()->whereIn('id', $visits->pluck('id'))->with('child', 'classRoom', 'checkedOutBy', 'family')->get()->all();
        });
    }

    public function listPayload(WalkinVisit $visit, bool $includeCode = false, bool $includeCare = false): array
    {
        $visit->loadMissing('child.family', 'classRoom', 'checkedInBy', 'checkedOutBy');
        $child = $visit->child;
        $earlier = $child
            ? WalkinVisit::query()->where('walkin_child_id', $child->id)->where('id', '<', $visit->id)->exists()
            : false;

        $payload = [
            'id' => $visit->id,
            'family_id' => $visit->walkin_family_id,
            'child_id' => $child?->id,
            'child_name' => $child?->full_name,
            'age_label' => $child?->ageLabel(),
            'class_name' => $visit->classRoom?->name,
            'class_room_id' => $visit->class_room_id,
            'guardian_name' => $child?->family?->guardian_name,
            'phone' => $child?->family?->phone,
            'relationship' => $child?->family?->relationship,
            'collector_name' => $visit->collector_name ?: $child?->family?->collector_name,
            'service_name' => $visit->service_name,
            'service_date' => optional($visit->service_date)->toDateString(),
            'status' => $visit->status,
            'status_label' => $visit->statusLabel(),
            'first_visit' => ! $earlier,
            'checked_in_at' => optional($visit->checked_in_at)->toIso8601String(),
            'checked_in_by' => $visit->checkedInBy?->name,
            'checked_out_at' => optional($visit->checked_out_at)->toIso8601String(),
            'checked_out_by' => $visit->checkedOutBy?->name,
            'participation_consent' => (bool) $child?->family?->participation_consent,
            'follow_up_consent' => (bool) $child?->family?->follow_up_consent,
        ];

        if ($includeCare) {
            $payload['care_notes'] = $child?->care_notes;
        }
        if ($includeCode) {
            $payload['pickup_code'] = $visit->pickup_code;
        }

        return $payload;
    }

    private function familyFor(User $staff, int $businessId, array $data): WalkinFamily
    {
        if (! empty($data['family_id'])) {
            $family = WalkinFamily::query()->where('business_id', $businessId)->whereKey($data['family_id'])->first();
            if (! $family) {
                throw ValidationException::withMessages(['family_id' => 'Choose a family from this church.']);
            }
            $family->update([
                'guardian_name' => trim($data['guardian_name']),
                'phone' => trim($data['phone']),
                'phone_digits' => $this->digits($data['phone']),
                'relationship' => trim($data['relationship']),
                'collector_name' => trim($data['collector_name']),
                'participation_consent' => true,
                'follow_up_consent' => (bool) ($data['follow_up_consent'] ?? false),
                'consented_at' => now(),
                'consented_by' => $staff->id,
            ]);

            return $family;
        }

        return WalkinFamily::query()->create([
            'business_id' => $businessId,
            'guardian_name' => trim($data['guardian_name']),
            'phone' => trim($data['phone']),
            'phone_digits' => $this->digits($data['phone']),
            'relationship' => trim($data['relationship']),
            'collector_name' => trim($data['collector_name']),
            'participation_consent' => true,
            'follow_up_consent' => (bool) ($data['follow_up_consent'] ?? false),
            'consent_version' => '2026-10-12',
            'consented_at' => now(),
            'consented_by' => $staff->id,
        ]);
    }

    private function childFor(WalkinFamily $family, array $data): WalkinChild
    {
        if (! empty($data['id'])) {
            $child = WalkinChild::query()->where('walkin_family_id', $family->id)->whereKey($data['id'])->first();
            if (! $child) {
                throw ValidationException::withMessages(['children' => 'That child is not in this family.']);
            }

            return $child;
        }

        $name = mb_strtolower(trim($data['full_name']));
        $match = $family->children()->get()->first(function (WalkinChild $child) use ($name, $data) {
            if (mb_strtolower($child->full_name) !== $name) {
                return false;
            }
            if (! empty($data['date_of_birth']) && $child->date_of_birth) {
                return $child->date_of_birth->toDateString() === $data['date_of_birth'];
            }

            return isset($data['age']) && (int) $child->age === (int) $data['age'];
        });

        if ($match && empty($data['confirm_new'])) {
            throw ValidationException::withMessages([
                'children' => $match->full_name.' may already be in this family. Use the existing child or confirm a new record.',
            ]);
        }

        return WalkinChild::query()->create([
            'business_id' => $family->business_id,
            'walkin_family_id' => $family->id,
            'full_name' => trim($data['full_name']),
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'age' => $data['age'] ?? null,
            'care_notes' => $data['care_notes'] ?? null,
        ]);
    }

    private function classFor(User $staff, int $businessId, int $classRoomId): ClassRoom
    {
        $class = ClassRoom::query()->where('business_id', $businessId)->whereKey($classRoomId)->first();
        if (! $class || ! $this->classes->canAccessClass($staff, $businessId, $class->id)) {
            throw ValidationException::withMessages(['children' => 'Choose a class at this church.']);
        }

        return $class;
    }

    private function serviceFor(int $businessId, int $serviceId, string $date): ChurchService
    {
        $service = ChurchService::query()->where('business_id', $businessId)->whereKey($serviceId)->first();
        if (! $service || ! $service->is_active || ! $service->occursOn($date)) {
            throw ValidationException::withMessages([
                'church_service_id' => 'Select an active service scheduled for this date.',
            ]);
        }

        return $service;
    }

    private function reserveCode(int $businessId, string $date): string
    {
        do {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (
            $code === '0000'
            || PickupCode::query()->where('business_id', $businessId)->whereDate('code_date', $date)->where('code', $code)->exists()
            || ChurchVisitorRegistration::query()->where('business_id', $businessId)->whereDate('service_date', $date)->where('pickup_code', $code)->where('status', '!=', 'cancelled')->exists()
            || WalkinVisit::query()->where('business_id', $businessId)->whereDate('service_date', $date)->where('pickup_code', $code)->exists()
        );

        return $code;
    }

    private function digits(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?: '';
    }

    private function normalizeCode(string $code): string
    {
        return str_pad(preg_replace('/\D/', '', $code) ?: '', 4, '0', STR_PAD_LEFT);
    }
}
