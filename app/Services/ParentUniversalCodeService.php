<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ParentGuardian;
use App\Models\ParentGuardianBusiness;
use Illuminate\Support\Facades\DB;

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

        // Strip common formatting characters for comparison (portable across MySQL versions).
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
     */
    public function attachToBusiness(
        ParentGuardian $parent,
        int $businessId,
        string $joinedVia,
        ?string $relationship = null
    ): ParentGuardianBusiness {
        return DB::transaction(function () use ($parent, $businessId, $joinedVia, $relationship) {
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
            if (! empty($parentUpdates)) {
                $parent->forceFill($parentUpdates)->save();
            }

            $this->ensureCode($parent);

            return $membership->fresh(['business']);
        });
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
