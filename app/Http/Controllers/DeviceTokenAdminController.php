<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesPushAdmin;
use App\Models\DeviceToken;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Services\PushConfigurationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceTokenAdminController extends Controller
{
    use AuthorizesPushAdmin;

    public function index(Request $request, PushConfigurationService $config): View
    {
        $this->authorizePushAdmin();

        $query = $this->deviceTokensQuery()->latest('last_used_at')->latest('id');

        if ($platform = $request->query('platform')) {
            $query->where('platform', $platform);
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('device_name', 'like', '%'.$search.'%')
                    ->orWhere('device_id', 'like', '%'.$search.'%')
                    ->orWhereHasMorph('tokenable', [User::class], function ($uq) use ($search) {
                        $uq->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })
                    ->orWhereHasMorph('tokenable', [ParentGuardian::class], function ($pq) use ($search) {
                        $pq->where('email', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    });
            });
        }

        $devices = $query->paginate(25)->withQueryString();

        $deviceStats = $config->deviceStats(fn () => $this->deviceTokensQuery());

        return view('push-notifications.devices', [
            'devices' => $devices,
            'deviceStats' => $deviceStats,
            'configChecks' => $this->isSuperAdmin() ? $config->checks() : [],
            'isSuperAdmin' => $this->isSuperAdmin(),
            'filters' => [
                'platform' => $platform,
                'search' => $search ?? '',
                'active_only' => $request->boolean('active_only', true),
            ],
        ]);
    }

    public static function ownerLabel(?object $owner): string
    {
        if ($owner instanceof User) {
            return $owner->name ?? 'Staff user';
        }

        if ($owner instanceof ParentGuardian) {
            return trim(($owner->first_name ?? '').' '.($owner->last_name ?? '')) ?: 'Parent';
        }

        return 'Unknown';
    }

    public static function ownerEmail(?object $owner): ?string
    {
        return $owner?->email ?? null;
    }

    public static function ownerType(?object $owner): string
    {
        if ($owner instanceof User) {
            return 'Staff';
        }

        if ($owner instanceof ParentGuardian) {
            return 'Parent';
        }

        return '—';
    }

    public static function maskToken(?string $token): string
    {
        if (! $token) {
            return '—';
        }

        if (str_starts_with($token, 'ExponentPushToken[') || str_starts_with($token, 'ExpoPushToken[')) {
            return strlen($token) > 28 ? substr($token, 0, 24).'…]' : $token;
        }

        return strlen($token) > 16 ? '…'.substr($token, -12) : $token;
    }
}
