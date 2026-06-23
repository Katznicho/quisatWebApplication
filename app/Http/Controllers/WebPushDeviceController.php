<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WebPushDeviceController extends Controller
{
    public function vapidPublicKey(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'public_key' => config('push.vapid.public_key'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
            'device_id' => 'nullable|string|max:191',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $deviceId = $validated['device_id'] ?? hash('sha256', $validated['subscription']['endpoint']);

        $token = DeviceToken::updateOrCreate(
            [
                'tokenable_type' => $user::class,
                'tokenable_id' => $user->getKey(),
                'device_id' => $deviceId,
            ],
            [
                'push_token' => json_encode($validated['subscription']),
                'platform' => 'web',
                'device_name' => $validated['device_name'] ?? Str::limit($request->userAgent() ?? 'Web browser', 255),
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Web push subscription saved.',
            'data' => ['id' => $token->uuid],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:191',
        ]);

        $user = Auth::user();

        DeviceToken::query()
            ->where('tokenable_type', $user::class)
            ->where('tokenable_id', $user->getKey())
            ->where('device_id', $validated['device_id'])
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Web push subscription removed.',
        ]);
    }
}
