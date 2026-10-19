<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePortalAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->get('portal_authenticated')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}

