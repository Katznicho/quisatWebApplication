<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BusinessScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Ensure user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Ensure user has a business association
        if (!$user->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'User is not associated with any business. Please contact support.'
            ], 403);
        }

        // Add business context to the request
        $request->merge([
            'business_id' => $user->business_id,
            'business' => $user->business,
            'user_type' => $this->getUserType($user)
        ]);

        return $next($request);
    }

    /**
     * Get user type based on business and role
     */
    private function getUserType($user)
    {
        if ($user->isAdmin()) {
            return 'super_admin';
        } elseif ($user->isBusinessAdmin()) {
            return 'business_admin';
        } elseif ($user->isStaff()) {
            return 'staff';
        } else {
            return 'user';
        }
    }
}