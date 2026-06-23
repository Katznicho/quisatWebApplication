<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
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

        $token = DeviceToken::updateOrCreate(
            [
                'tokenable_type' => $owner::class,
                'tokenable_id' => $owner->getKey(),
                'device_id' => $validated['device_id'],
            ],
            [
                'push_token' => $validated['push_token'],
                'platform' => $validated['platform'],
                'device_name' => $validated['device_name'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

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

        DeviceToken::query()
            ->where('tokenable_type', $owner::class)
            ->where('tokenable_id', $owner->getKey())
            ->where('device_id', $validated['device_id'])
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Device unregistered from push notifications.',
        ]);
    }
}
