<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DeviceToken;
use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait AuthorizesPushAdmin
{
    protected function authorizePushAdmin(): void
    {
        if (! Auth::check()) {
            abort(403);
        }
    }

    protected function isSuperAdmin(): bool
    {
        return Auth::check() && (int) Auth::user()->business_id === 1;
    }

    protected function deviceTokensQuery()
    {
        $query = DeviceToken::query()->with('tokenable');

        if (! $this->isSuperAdmin()) {
            $businessId = Auth::user()->business_id;

            $query->where(function ($q) use ($businessId) {
                $q->whereHasMorph('tokenable', [User::class], fn ($uq) => $uq->where('business_id', $businessId))
                    ->orWhereHasMorph('tokenable', [ParentGuardian::class], fn ($pq) => $pq->where('business_id', $businessId));
            });
        }

        return $query;
    }
}
