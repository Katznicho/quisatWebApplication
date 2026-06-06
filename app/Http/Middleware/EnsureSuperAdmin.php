<?php

namespace App\Http\Middleware;

use App\Support\TenantScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! TenantScope::isSuperAdmin()) {
            abort(403, 'Administrator access required.');
        }

        return $next($request);
    }
}
