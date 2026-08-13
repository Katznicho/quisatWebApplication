<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:191',
            'push_token' => 'required|string',
            'platform' => 'required|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        $owner = $request->user();
        $attributes = [
            'push_token' => $validated['push_token'],
            'platform' => $validated['platform'],
            'device_name' => $validated['device_name'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'is_active' => true,
            'last_used_at' => now(),
        ];

        $token = DeviceToken::updateOrCreate(
            [
                'tokenable_type' => $owner::class,
                'tokenable_id' => $owner->getKey(),
                'device_id' => $validated['device_id'],
            ],
            $attributes
        );

        foreach ($this->linkedOwners($owner) as $linked) {
            DeviceToken::updateOrCreate(
                [
                    'tokenable_type' => $linked::class,
                    'tokenable_id' => $linked->getKey(),
                    'device_id' => $validated['device_id'],
                ],
                $attributes
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Device registered for push notifications.',
            'data' => [
                'id' => $token->uuid,
                'device_id' => $token->device_id,
                'platform' => $token->platform,
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:191',
        ]);

        $owner = $request->user();
        $owners = collect([$owner])->merge($this->linkedOwners($owner));

        DeviceToken::query()
            ->where('device_id', $validated['device_id'])
            ->where(function ($query) use ($owners) {
                foreach ($owners as $tokenOwner) {
                    $query->orWhere(function ($ownerQuery) use ($tokenOwner) {
                        $ownerQuery
                            ->where('tokenable_type', $tokenOwner::class)
                            ->where('tokenable_id', $tokenOwner->getKey());
                    });
                }
            })
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Device unregistered from push notifications.',
        ]);
    }

    /**
     * @return array<int, User|ParentGuardian>
     */
    protected function linkedOwners($owner): array
    {
        $email = strtolower(trim((string) ($owner->email ?? '')));
        if ($email === '') {
            return [];
        }

        if ($owner instanceof ParentGuardian) {
            $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

            return $user ? [$user] : [];
        }

        if ($owner instanceof User) {
            $parent = ParentGuardian::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

            return $parent ? [$parent] : [];
        }

        return [];
    }
}
