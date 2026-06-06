<?php

namespace App\Http\Controllers\Concerns;

use App\Support\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesBusinessResource
{
    protected function currentBusinessId(): ?int
    {
        return TenantScope::businessId();
    }

    protected function isSuperAdmin(): bool
    {
        return TenantScope::isSuperAdmin();
    }

    protected function authorizeBusinessResource(Model $record, string $column = 'business_id'): void
    {
        TenantScope::authorizeModel($record, $column);
    }

    protected function scopedBusinessQuery(Builder $query, string $column = 'business_id'): Builder
    {
        return TenantScope::apply($query, $column);
    }
}
