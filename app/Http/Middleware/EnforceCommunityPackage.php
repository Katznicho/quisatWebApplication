<?php

namespace App\Http\Middleware;

use App\Support\OrganisationPackage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCommunityPackage
{
    public function handle(Request $request, Closure $next): Response
    {
        $service = OrganisationPackage::serviceForPath($request->path());
        if (! $service) {
            return $next($request);
        }

        $business = OrganisationPackage::businessFromRequest($request);
        if (! $business || OrganisationPackage::allows($business, $service)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'This service is not included in your organisation package.',
        ], 403);
    }
}
