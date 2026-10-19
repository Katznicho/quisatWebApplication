<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Authentication is handled in the controller
        // This middleware just marks the request as API
        $request->attributes->set('is_api', true);
        
        return $next($request);
    }
}
