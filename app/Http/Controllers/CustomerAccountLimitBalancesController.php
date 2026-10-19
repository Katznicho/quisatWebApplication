<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SoapClient;
use stdClass;

class CustomerAccountLimitBalancesController extends Controller
{
    public function index()
    {
        return view('reports.customer-account-limit-balances');
    }

    public function store(Request $request)
    {
        try {
            $customerNo = session('customerNumber');

            if (!$customerNo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer number not found in session. Please log in again.',
                ], 403);
            }

            $webServiceUrl = config('app.webService');
            $username = config('app.soapUsername');
            $password = config('app.soapPassword');

            if (!$webServiceUrl || !$username || !$password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report service is not configured. Please contact the administrator.',
                ], 500);
            }

            $options = [
                'login' => $username,
                'password' => $password,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            try {
                $service = new SoapClient($webServiceUrl, $options);
            } catch (\Throwable $e) {
                Log::error('Customer account limit balances: Failed to create SOAP client', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to report service: ' . $e->getMessage(),
                ], 500);
            }

            $params = new stdClass();
            $params->customerNo = trim($customerNo);

            Log::info('Customer account limit balances: calling CustomerAccountLimitBalances', [
                'customerNo' => $customerNo,
            ]);

            try {
                $result = $service->CustomerAccountLimitBalances($params);
            } catch (\SoapFault $e) {
                Log::error('Customer account limit balances: SOAP fault', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch account limits: ' . $e->getMessage(),
                ], 500);
            }

            $returnValue = $result->return_value ?? null;

            Log::info('Customer account limit balances: raw response', [
                'return_value_type' => gettype($returnValue),
                'return_value_length' => is_string($returnValue) ? strlen($returnValue) : null,
                'return_value_preview' => is_string($returnValue) ? substr($returnValue, 0, 200) : $returnValue,
            ]);

            if (empty($returnValue)) {
                Log::warning('Customer account limit balances: empty return value');
                return response()->json([
                    'success' => false,
                    'message' => 'No data available for your account limits.',
                ], 422);
            }

            // Check if already valid base64
            $isBase64 = base64_decode($returnValue, true) !== false;

            Log::info('Customer account limit balances: base64 check', [
                'is_valid_base64' => $isBase64,
                'first_bytes_hex' => bin2hex(substr($isBase64 ? base64_decode($returnValue) : $returnValue, 0, 8)),
            ]);

            $pdf = $isBase64 ? $returnValue : base64_encode($returnValue);

            return response()->json([
                'success' => true,
                'pdf' => $pdf,
            ]);
        } catch (\SoapFault $e) {
            Log::error('SOAP Fault in customer account limit balances', [
                'message' => $e->getMessage(),
                'fault_code' => $e->faultcode ?? 'N/A',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch account limits: ' . $e->getMessage(),
            ], 500);
        } catch (\Throwable $th) {
            Log::error('Error fetching customer account limit balances', [
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch account limits: ' . $th->getMessage(),
            ], 500);
        }
    }
}
