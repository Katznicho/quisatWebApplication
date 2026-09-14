<?php

namespace App\Services;

use App\Mail\ParentLinkedToBusinessMail;
use App\Models\Business;
use App\Models\ClinicPatient;
use App\Models\ParentChild;
use App\Models\ParentGuardian;
use App\Models\ParentGuardianBusiness;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ParentUniversalCodeService
{
    public const CODE_PREFIX = 'QSP-';

    /** Uppercase alphanumeric without ambiguous 0/O/1/I/L. */
    public const CODE_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const CODE_LENGTH = 8;

    public function generateUniqueCode(): string
    {
        do {
            $code = self::CODE_PREFIX.$this->randomSegment(self::CODE_LENGTH);
        } while (ParentGuardian::withTrashed()->where('universal_code', $code)->exists());

        return $code;
    }

    public function ensureCode(ParentGuardian $parent): string
    {
        if (! empty($parent->universal_code)) {
            return $parent->universal_code;
        }

        $code = $this->generateUniqueCode();
        $parent->forceFill(['universal_code' => $code])->save();

        return $code;
    }

    public function regenerate(ParentGuardian $parent): string
    {
        $code = $this->generateUniqueCode();
        $parent->forceFill(['universal_code' => $code])->save();

        return $code;
    }

    public function universalLink(?string $code): ?string
    {
        if (empty($code)) {
            return null;
        }

        return rtrim((string) config('app.url'), '/').'/join/'.$code;
    }

    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return $normalized === '' ? null : $normalized;
    }

    /**
     * Canonical phone for matching: digits only (local or international).
     */
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits === '' ? null : $digits;
    }

    public function findByNormalizedEmail(string $email, bool $withTrashed = false): ?ParentGuardian
    {
        $normalized = $this->normalizeEmail($email);
        if (! $normalized) {
            return null;
        }

        $query = $withTrashed ? ParentGuardian::withTrashed() : ParentGuardian::query();

        return $query->whereRaw('LOWER(TRIM(email)) = ?', [$normalized])->first();
    }

    public function findByNormalizedPhone(string $phone, bool $withTrashed = false): ?ParentGuardian
    {
        $normalized = $this->normalizePhone($phone);
        if (! $normalized) {
            return null;
        }

        $query = $withTrashed ? ParentGuardian::withTrashed() : ParentGuardian::query();

        return $query
            ->whereNotNull('phone')
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') = ?",
                [$normalized]
            )
            ->first();
    }

    public function findByCode(string $code): ?ParentGuardian
    {
        $code = strtoupper(trim($code));

        return ParentGuardian::where('universal_code', $code)->first();
    }

    public function isClinicBusiness(Business $business): bool
    {
        if ((int) $business->id === 1) {
            return false;
        }

        return $business->hasFeatureByName('Kids Clinics');
    }

    /**
     * Idempotently attach a parent to a business and upgrade guest → linked.
     * Also imports any children the parent registered in the app.
     *
     * @param  bool  $sendNotification  Set false for backfills so historical links are not emailed.
     */
    public function attachToBusiness(
        ParentGuardian $parent,
        int $businessId,
        string $joinedVia,
        ?string $relationship = null,
        bool $sendNotification = true
    ): ParentGuardianBusiness {
        $createdNewMembership = false;

        $membership = DB::transaction(function () use ($parent, $businessId, $joinedVia, $relationship, &$createdNewMembership) {
            $membership = ParentGuardianBusiness::query()
                ->where('parent_guardian_id', $parent->id)
                ->where('business_id', $businessId)
                ->lockForUpdate()
                ->first();

            if ($membership) {
                $updates = [];
                if ($membership->status !== 'active') {
                    $updates['status'] = 'active';
                    $updates['joined_at'] = $membership->joined_at ?? now();
                }
                if ($relationship !== null && $membership->relationship !== $relationship) {
                    $updates['relationship'] = $relationship;
                }
                if (! empty($updates)) {
                    $membership->fill($updates)->save();
                }
            } else {
                $createdNewMembership = true;
                $membership = ParentGuardianBusiness::create([
                    'parent_guardian_id' => $parent->id,
                    'business_id' => $businessId,
                    'relationship' => $relationship,
                    'joined_via' => $joinedVia,
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            }

            $parentUpdates = [];
            if ($parent->account_type !== 'linked') {
                $parentUpdates['account_type'] = 'linked';
            }
            if (empty($parent->business_id)) {
                $parentUpdates['business_id'] = $businessId;
            }
            if ($relationship && empty($parent->relationship)) {
                $parentUpdates['relationship'] = $relationship;
            }
            if (! empty($parentUpdates)) {
                $parent->forceFill($parentUpdates)->save();
            }

            $this->ensureCode($parent);
            $this->importChildrenToBusiness($parent->fresh(['children', 'students', 'clinicPatients']), $businessId);

            return $membership->fresh(['business']);
        });

        if ($sendNotification && $createdNewMembership && $membership?->business && filled($parent->email)) {
            try {
                Mail::to($parent->email)->send(new ParentLinkedToBusinessMail($parent->fresh(), $membership->business));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $membership;
    }

    /**
     * Create Kids Church / school student records from every child on the parent account:
     * app child profiles, students at other schools, and clinic patients.
     *
     * @return array{students: int, patients: int}
     */
    public function importChildrenToBusiness(ParentGuardian $parent, int $businessId): array
    {
        $business = Business::find($businessId);
        if (! $business) {
            return ['students' => 0, 'patients' => 0];
        }

        $studentsCreated = 0;
        $patientsCreated = 0;
        $isClinic = $this->isClinicBusiness($business);
        $clinicImporter = $isClinic ? app(ClinicPatientImportService::class) : null;

        foreach ($this->childProfilesForImport($parent) as $profile) {
            $this->ensureParentChildFromProfile($parent, $profile);

            $student = $this->findStudentForParent(
                $parent,
                $businessId,
                $profile['first_name'],
                $profile['last_name'],
                $profile['date_of_birth']
            );

            if (! $student) {
                $student = Student::create([
                    'first_name' => $profile['first_name'],
                    'last_name' => $profile['last_name'],
                    'email' => $this->uniqueStudentEmail($parent, $profile['seed']),
                    'phone' => $profile['phone'] ?: $parent->phone,
                    'date_of_birth' => $profile['date_of_birth'],
                    'gender' => $profile['gender'],
                    'address' => $profile['address'] ?: $parent->address,
                    'city' => $profile['city'] ?: $parent->city,
                    'country' => $profile['country'] ?: $parent->country,
                    'student_id' => $this->uniqueStudentId(),
                    'admission_date' => now()->toDateString(),
                    'business_id' => $businessId,
                    'parent_guardian_id' => $parent->id,
                    'status' => 'active',
                    'photo' => $profile['photo'],
                    'allergies' => $profile['allergies'],
                    'medical_notes' => $profile['medical_notes'],
                    'dietary_restrictions' => $profile['dietary_restrictions'],
                    'emergency_contacts' => $profile['emergency_contacts'],
                ]);
                $studentsCreated++;
            }

            $patientsCreated += $this->attachClinicPatientIfNeeded($clinicImporter, $business, $businessId, $student);
        }

        return ['students' => $studentsCreated, 'patients' => $patientsCreated];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function childProfilesForImport(ParentGuardian $parent): array
    {
        $parent->loadMissing(['children', 'students', 'clinicPatients.student']);
        $profiles = [];

        foreach ($parent->children as $child) {
            $this->mergeChildProfile($profiles, [
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
                'date_of_birth' => optional($child->date_of_birth)->toDateString(),
                'gender' => $child->gender,
                'phone' => $child->phone,
                'address' => $child->address,
                'city' => $child->city,
                'country' => $child->country,
                'photo' => null,
                'allergies' => $child->allergies,
                'medical_notes' => $child->medical_notes,
                'dietary_restrictions' => null,
                'emergency_contacts' => null,
                'seed' => (string) ($child->uuid ?: Str::uuid()),
            ]);
        }

        foreach ($parent->students as $student) {
            $this->mergeChildProfile($profiles, [
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'date_of_birth' => optional($student->date_of_birth)->toDateString(),
                'gender' => $student->gender,
                'phone' => $student->phone,
                'address' => $student->address,
                'city' => $student->city,
                'country' => $student->country,
                'photo' => $student->photo,
                'allergies' => $this->textFromMaybeArray($student->allergies),
                'medical_notes' => $student->medical_notes,
                'dietary_restrictions' => $student->dietary_restrictions,
                'emergency_contacts' => $student->emergency_contacts,
                'seed' => (string) ($student->uuid ?: Str::uuid()),
            ]);
        }

        foreach ($parent->clinicPatients as $patient) {
            if ($patient->student && empty($patient->student->parent_guardian_id)) {
                $patient->student->update(['parent_guardian_id' => $parent->id]);
            }

            $this->mergeChildProfile($profiles, [
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'date_of_birth' => optional($patient->date_of_birth)->toDateString(),
                'gender' => $patient->gender,
                'phone' => $parent->phone,
                'address' => $parent->address,
                'city' => $parent->city,
                'country' => $parent->country,
                'photo' => $patient->photo,
                'allergies' => $this->textFromMaybeArray($patient->allergies),
                'medical_notes' => null,
                'dietary_restrictions' => null,
                'emergency_contacts' => $patient->emergency_contacts,
                'seed' => (string) ($patient->uuid ?: Str::uuid()),
            ]);
        }

        return array_values($profiles);
    }

    protected function mergeChildProfile(array &$profiles, array $profile): void
    {
        $first = trim((string) ($profile['first_name'] ?? ''));
        $last = trim((string) ($profile['last_name'] ?? ''));
        if ($first === '' || $last === '') {
            return;
        }

        $profile['first_name'] = $first;
        $profile['last_name'] = $last;
        $key = Str::lower($first.'|'.$last.'|'.($profile['date_of_birth'] ?? ''));

        if (! isset($profiles[$key])) {
            $profiles[$key] = $profile;

            return;
        }

        foreach ($profile as $field => $value) {
            if (($profiles[$key][$field] === null || $profiles[$key][$field] === '') && $value) {
                $profiles[$key][$field] = $value;
            }
        }
    }

    protected function findStudentForParent(
        ParentGuardian $parent,
        int $businessId,
        string $firstName,
        string $lastName,
        ?string $dateOfBirth
    ): ?Student {
        $query = Student::query()
            ->where('parent_guardian_id', $parent->id)
            ->where('business_id', $businessId)
            ->where('first_name', $firstName)
            ->where('last_name', $lastName);

        if ($dateOfBirth) {
            $query->whereDate('date_of_birth', $dateOfBirth);
        }

        return $query->first();
    }

    protected function ensureParentChildFromProfile(ParentGuardian $parent, array $profile): void
    {
        if (! filled($profile['first_name']) || ! filled($profile['last_name']) || empty($profile['date_of_birth'])) {
            return;
        }

        $gender = strtolower((string) ($profile['gender'] ?? ''));
        if (! in_array($gender, ['male', 'female', 'other'], true)) {
            $gender = 'other';
        }

        $exists = ParentChild::query()
            ->where('parent_guardian_id', $parent->id)
            ->where('first_name', $profile['first_name'])
            ->where('last_name', $profile['last_name'])
            ->whereDate('date_of_birth', $profile['date_of_birth'])
            ->exists();

        if ($exists) {
            return;
        }

        $parent->children()->create([
            'first_name' => $profile['first_name'],
            'last_name' => $profile['last_name'],
            'date_of_birth' => $profile['date_of_birth'],
            'gender' => $gender,
            'phone' => $profile['phone'] ?: $parent->phone,
            'address' => $profile['address'] ?: $parent->address,
            'city' => $profile['city'] ?: $parent->city,
            'country' => $profile['country'] ?: $parent->country,
            'medical_notes' => $profile['medical_notes'],
            'allergies' => $this->textFromMaybeArray($profile['allergies']),
        ]);
    }

    protected function textFromMaybeArray(mixed $value): ?string
    {
        if (is_array($value)) {
            $parts = array_filter(array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $value));

            return $parts === [] ? null : implode(', ', $parts);
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    protected function attachClinicPatientIfNeeded($clinicImporter, Business $business, int $businessId, Student $student): int
    {
        if (! $clinicImporter) {
            return 0;
        }

        $existed = ClinicPatient::query()
            ->where('business_id', $businessId)
            ->where('student_id', $student->id)
            ->exists();

        $clinicImporter->attachStudentToClinic($student, $business);

        return $existed ? 0 : 1;
    }

    protected function uniqueChildEmail(ParentGuardian $parent, ParentChild $child): string
    {
        return $this->uniqueStudentEmail($parent, (string) ($child->uuid ?: Str::uuid()));
    }

    protected function uniqueStudentEmail(ParentGuardian $parent, string $seed): string
    {
        $base = 'child.'.Str::lower(Str::substr($seed, 0, 8)).'.'.$parent->id;
        $email = $base.'@quisat.parent';
        $i = 1;
        while (Student::withTrashed()->where('email', $email)->exists()) {
            $email = $base.'.'.$i.'@quisat.parent';
            $i++;
        }

        return $email;
    }

    protected function uniqueStudentId(): string
    {
        do {
            $studentId = 'STU-'.strtoupper(Str::random(8));
        } while (Student::withTrashed()->where('student_id', $studentId)->exists());

        return $studentId;
    }

    protected function randomSegment(int $length): string
    {
        $charset = self::CODE_CHARSET;
        $max = strlen($charset) - 1;
        $segment = '';

        for ($i = 0; $i < $length; $i++) {
            $segment .= $charset[random_int(0, $max)];
        }

        return $segment;
    }
}
