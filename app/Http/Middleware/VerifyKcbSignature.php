<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyKcbSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Bypass verification in local/testing to ease development
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $signatureHeader = $request->header('signature');

        if (empty($signatureHeader)) {
            Log::warning('KCB signature missing on incoming request', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'statusCode' => 1,
                'statusMessage' => 'Missing signature header',
            ], 401);
        }

        $publicKeyPath = storage_path('keys/kcb_uat_publickey.pem');

        if (! file_exists($publicKeyPath)) {
            Log::error('KCB public key file not found', [
                'path' => $publicKeyPath,
            ]);

            return response()->json([
                'statusCode' => 1,
                'statusMessage' => 'Signature verification configuration error',
            ], 500);
        }

        $publicKeyContents = file_get_contents($publicKeyPath);

        if ($publicKeyContents === false) {
            Log::error('Failed to read KCB public key file', [
                'path' => $publicKeyPath,
            ]);

            return response()->json([
                'statusCode' => 1,
                'statusMessage' => 'Signature verification configuration error',
            ], 500);
        }

        $publicKey = openssl_pkey_get_public($publicKeyContents);

        if ($publicKey === false) {
            Log::error('Invalid KCB public key contents', [
                'path' => $publicKeyPath,
            ]);

            return response()->json([
                'statusCode' => 1,
                'statusMessage' => 'Signature verification configuration error',
            ], 500);
        }

        $payload = $request->getContent();
        $decodedSignature = base64_decode($signatureHeader, true);

        if ($decodedSignature === false) {
            Log::warning('KCB signature header is not valid base64', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'statusCode' => 1,
                'statusMessage' => 'Invalid signature format',
            ], 401);
        }

        $verificationResult = openssl_verify($payload, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);

        // Free the key resource
        if (is_resource($publicKey)) {
            openssl_free_key($publicKey);
        }

        if ($verificationResult !== 1) {
            Log::warning('KCB signature verification failed', [
                'path' => $request->path(),
                'ip' => $request->ip(),
                'verification_result' => $verificationResult,
            ]);

            return response()->json([
                'statusCode' => 1,
                'statusMessage' => 'Invalid signature',
            ], 401);
        }

        return $next($request);
    }
}
