<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $callbackUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.marz_wallet.api_url', 'https://wallet.wearemarz.com/api/v1');
        $this->apiKey = config('services.marz_wallet.api_key');
        // Use configured callback URL or generate API webhook URL
        $this->callbackUrl = config('services.marz_wallet.callback_url') 
            ?: url('/api/webhooks/payment');
    }

    /**
     * Collect money from mobile money
     *
     * @param string $phoneNumber Phone number with country code (e.g., +256700000000)
     * @param float $amount Amount in UGX (500 - 10,000,000)
     * @param string $reference UUID format reference
     * @param string $description Payment description
     * @return array
     * @throws Exception
     */
    public function collectMoney(string $phoneNumber, float $amount, string $reference, string $description = 'Account topup'): array
    {
        try {
            // Validate amount range
            if ($amount < 500 || $amount > 10000000) {
                throw new Exception('Amount must be between 500 and 10,000,000 UGX');
            }

            // Validate phone number format
            if (!preg_match('/^\+256\d{9}$/', $phoneNumber)) {
                throw new Exception('Invalid phone number format. Must be +256 followed by 9 digits');
            }

            // Validate reference is UUID format
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $reference)) {
                throw new Exception('Reference must be in UUID format');
            }

            Log::info('Initiating mobile money collection', [
                'phone_number' => $phoneNumber,
                'amount' => $amount,
                'reference' => $reference,
            ]);

            // Prepare Basic Auth credentials
            // The key in .env is already in base64 format - use it directly
            if (empty($this->apiKey)) {
                throw new Exception('MARZ_WALLET_API_KEY is not configured in .env file');
            }
            
            // Use the key directly as-is (already base64 encoded)
            $credentials = $this->apiKey;
            
            // Make API request
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
            ])->asForm()->post($this->apiUrl . '/collect-money', [
                'phone_number' => $phoneNumber,
                'amount' => (string) $amount,
                'country' => 'UG',
                'reference' => $reference,
                'description' => $description,
                'callback_url' => $this->callbackUrl,
            ]);

            $responseData = $response->json();

            if (!$response->successful()) {
                $errorMessage = $responseData['message'] ?? 'Failed to initiate payment collection';
                Log::error('Payment collection failed', [
                    'status' => $response->status(),
                    'response' => $responseData,
                    'request_url' => $this->apiUrl . '/collect-money',
                    'api_key_configured' => !empty($this->apiKey),
                ]);
                
                // Provide more helpful error message
                if ($response->status() === 401) {
                    throw new Exception('Invalid API credentials. Please check that MARZ_WALLET_API_KEY is correctly set in your .env file.');
                }
                
                throw new Exception($errorMessage);
            }

            Log::info('Payment collection initiated successfully', [
                'transaction_uuid' => $responseData['data']['transaction']['uuid'] ?? null,
                'reference' => $reference,
            ]);

            return [
                'success' => true,
                'data' => $responseData['data'] ?? [],
                'message' => $responseData['message'] ?? 'Collection initiated successfully',
            ];

        } catch (Exception $e) {
            Log::error('Payment collection error', [
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber,
                'amount' => $amount,
            ]);
            
            throw $e;
        }
    }

    /**
     * Calculate the charge amount based on percentage
     * 
     * @param float $amount The base amount
     * @param float $percentage The service charge percentage (default 3.00)
     * @return float The calculated charge amount
     */
    public function calculateCharge(float $amount, float $percentage = 3.00): float
    {
        return round($amount * ($percentage / 100), 2);
    }

    /**
     * Calculate the total amount including charge
     * 
     * @param float $amount The base amount
     * @param float $percentage The service charge percentage (default 3.00)
     * @return float The total amount including charge
     */
    public function calculateTotalWithCharge(float $amount, float $percentage = 3.00): float
    {
        return $amount + $this->calculateCharge($amount, $percentage);
    }
}
