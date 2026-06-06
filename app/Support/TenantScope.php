<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TenantScope
{
    public static function businessId(): ?int
    {
        $user = Auth::user();

        if (! $user || ! isset($user->business_id)) {
            return null;
        }

        return (int) $user->business_id;
    }

    public static function isSuperAdmin(): bool
    {
        return self::businessId() === 1;
    }

    public static function apply(Builder $query, string $column = 'business_id'): Builder
    {
        if (! self::isSuperAdmin()) {
            $query->where($column, self::businessId());
        }

        return $query;
    }

    public static function authorizeModel(Model $record, string $column = 'business_id'): void
    {
        if (self::isSuperAdmin()) {
            return;
        }

        $businessId = self::businessId();

        if (! $businessId || (int) $record->{$column} !== $businessId) {
            abort(403, 'You are not allowed to access this record.');
        }
    }
}
