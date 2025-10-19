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

        // Handle both User and ParentGuardian models
        $businessId = null;
        $business = null;
        $userType = null;

        if ($user instanceof \App\Models\User) {
            // Regular user
            $businessId = $user->business_id;
            $business = $user->business;
            $userType = $this->getUserType($user);
        } elseif ($user instanceof \App\Models\ParentGuardian) {
            // Parent/Guardian
            $businessId = $user->business_id;
            $business = $user->business;
            $userType = 'parent_guardian';
        }

        // Ensure user has a business association
        if (!$businessId || !$business) {
            $userTypeName = $user instanceof \App\Models\ParentGuardian ? 'Parent/Guardian' : 'User';
            return response()->json([
                'success' => false,
                'message' => $userTypeName . ' is not associated with any business. Please contact support.'
            ], 403);
        }

        // Add business context to the request
        $request->merge([
            'business_id' => $businessId,
            'business' => $business,
            'user_type' => $userType,
            'authenticated_user' => $user
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