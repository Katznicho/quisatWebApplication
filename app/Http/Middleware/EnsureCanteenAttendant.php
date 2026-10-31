<?php

namespace App\Http\Middleware;

use App\Models\CanteenAttendant;
use App\Services\CanteenAttendantAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quisat Attendant routes: the token must belong to an active attendant whose canteen
 * is active and whose school has Pocket Pay enabled.
 */
class EnsureCanteenAttendant
{
    public function __construct(protected CanteenAttendantAuthService $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $attendant = $request->user();

        if (! $attendant instanceof CanteenAttendant) {
            return response()->json([
                'success' => false,
                'message' => 'Only canteen attendants can use this service.',
            ], 403);
        }

        $attendant->loadMissing('canteen', 'business');
        $this->auth->assertCanWork($attendant);

        return $next($request);
    }
}
