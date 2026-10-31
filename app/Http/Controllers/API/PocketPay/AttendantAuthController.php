<?php

namespace App\Http\Controllers\Api\PocketPay;

use App\Http\Controllers\Controller;
use App\Models\CanteenAttendant;
use App\Services\CanteenAttendantAuthService;
use App\Support\PocketPayPresenter;
use Illuminate\Http\Request;

/**
 * Quisat Attendant: account setup, login, forgot PIN and change PIN.
 */
class AttendantAuthController extends Controller
{
    public function __construct(protected CanteenAttendantAuthService $auth) {}

    /**
     * Send a one-time PIN for first-time setup or forgot PIN.
     * Always answers the same way so phone numbers cannot be probed.
     */
    public function requestOtp(Request $request)
    {
        $validated = $request->validate(['phone' => 'required|string|max:20']);

        $attendant = $this->auth->findByPhone($validated['phone']);

        if ($attendant && $attendant->status !== CanteenAttendant::STATUS_DISABLED) {
            $this->auth->sendOtp($attendant);
        }

        return response()->json([
            'success' => true,
            'message' => 'If this number belongs to an attendant, a one-time PIN has been sent by SMS.',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'otp' => 'required|string|size:4',
            'pin' => 'required|string|size:4|confirmed',
        ]);

        $attendant = $this->auth->completeWithOtp($validated['phone'], $validated['otp'], $validated['pin']);
        $this->auth->assertCanWork($attendant);

        return $this->tokenResponse($attendant, 'Your PIN is set. Welcome to Quisat Attendant.');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'pin' => 'required|string|size:4',
            'device_name' => 'nullable|string|max:100',
        ]);

        $attendant = $this->auth->login($validated['phone'], $validated['pin']);

        return $this->tokenResponse($attendant, 'Logged in.', $validated['device_name'] ?? null);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => ['attendant' => PocketPayPresenter::attendant($request->user())],
        ]);
    }

    public function changePin(Request $request)
    {
        $validated = $request->validate([
            'current_pin' => 'required|string|size:4',
            'pin' => 'required|string|size:4|confirmed',
        ]);

        $this->auth->changePin($request->user(), $validated['current_pin'], $validated['pin']);

        return response()->json(['success' => true, 'message' => 'Your PIN has been changed.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }

    protected function tokenResponse(CanteenAttendant $attendant, string $message, ?string $deviceName = null)
    {
        $attendant->loadMissing('canteen', 'business');
        $token = $attendant->createToken($deviceName ?: 'quisat-attendant', ['attendant'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'token' => $token,
                'attendant' => PocketPayPresenter::attendant($attendant),
            ],
        ]);
    }
}
