<?php

namespace App\Support;

use App\Models\ParentGuardian;
use App\Models\User;

class ReviewAuthor
{
    public static function userId(mixed $user): ?int
    {
        return $user instanceof User ? $user->id : null;
    }

    public static function parentGuardianId(mixed $user): ?int
    {
        return $user instanceof ParentGuardian ? $user->id : null;
    }

    public static function displayName(mixed $user): string
    {
        if ($user instanceof User) {
            return (string) ($user->name ?? 'Customer');
        }

        if ($user instanceof ParentGuardian) {
            return trim((string) ($user->full_name ?? '')) ?: 'Customer';
        }

        return 'Customer';
    }

    public static function scopeForUser($query, mixed $user): void
    {
        $userId = self::userId($user);
        $parentId = self::parentGuardianId($user);

        if ($userId !== null) {
            $query->where('user_id', $userId);

            return;
        }

        if ($parentId !== null) {
            $query->where('parent_guardian_id', $parentId);

            return;
        }

        $query->whereRaw('1 = 0');
    }
}
