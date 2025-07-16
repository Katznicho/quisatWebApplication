<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiKey;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            return response()->json(['message' => 'Missing or invalid Authorization header.'], 401);
        }

        $encoded = substr($authHeader, 6); // Remove 'Basic '
        $decoded = base64_decode($encoded);

        if (!$decoded || !str_contains($decoded, ':')) {
            return response()->json(['message' => 'Invalid token format.'], 401);
        }

        [$key, $secret] = explode(':', $decoded, 2);

        $apiKey = ApiKey::where('key', $key)->where('secret', $secret)->first();

        if (!$apiKey) {
            return response()->json(['message' => 'Invalid API credentials.'], 401);
        }

        // Attach business info to the request
        $request->merge([
            'business_id' => $apiKey->business_id,
            'api_key_id' => $apiKey->id,
        ]);

        return $next($request);
    }
}
