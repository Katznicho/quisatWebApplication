<?php

namespace App\Http\Middleware;

use App\Models\CanteenAttendant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanctum accepts any token-holding model on auth:sanctum routes. Attendant tokens are only
 * meant for the Quisat Attendant endpoints, so refuse them everywhere else.
 */
class RejectCanteenAttendantTokens
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()
            && ! $request->is('api/v1/attendant/*')
            && $request->user('sanctum') instanceof CanteenAttendant) {
            return response()->json([
                'success' => false,
                'message' => 'This token can only be used in the Quisat Attendant app.',
            ], 403);
        }

        return $next($request);
    }
}
