<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SoapClient;
use stdClass;

class CustomerSalesEntriesController extends Controller
{
    public function index()
    {
        return view('reports.customer-sales-entries');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDateObj = new \DateTime($request->input('start_date'));
            $endDateObj = new \DateTime($request->input('end_date'));
            $daysDiff = $startDateObj->diff($endDateObj)->days;

            if ($daysDiff > 365) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date range cannot exceed 365 days.',
                ], 400);
            }

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
                Log::error('Customer sales entries: Failed to create SOAP client', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to report service: ' . $e->getMessage(),
                ], 500);
            }

            $params = new stdClass();
            $params->customerNo = trim($customerNo);
            $params->startDate = $request->input('start_date');
            $params->endDate = $request->input('end_date');

            Log::info('Customer sales entries: calling CustomerSalesEntries', [
                'customerNo' => $customerNo,
                'startDate' => $params->startDate,
                'endDate' => $params->endDate,
            ]);

            try {
                $result = $service->CustomerSalesEntries($params);
            } catch (\SoapFault $e) {
                Log::error('Customer sales entries: SOAP fault', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch sales entries: ' . $e->getMessage(),
                ], 500);
            }

            $returnValue = $result->return_value ?? null;

            Log::info('Customer sales entries: raw response', [
                'return_value_type' => gettype($returnValue),
                'return_value_length' => is_string($returnValue) ? strlen($returnValue) : null,
                'return_value_preview' => is_string($returnValue) ? substr($returnValue, 0, 200) : $returnValue,
            ]);

            if (empty($returnValue)) {
                Log::warning('Customer sales entries: empty return value');
                return response()->json([
                    'success' => false,
                    'message' => 'No sales entries found for the selected date range.',
                ], 422);
            }

            // Check if already valid base64
            $isBase64 = base64_decode($returnValue, true) !== false;

            Log::info('Customer sales entries: base64 check', [
                'is_valid_base64' => $isBase64,
                'first_bytes_hex' => bin2hex(substr($isBase64 ? base64_decode($returnValue) : $returnValue, 0, 8)),
            ]);

            $pdf = $isBase64 ? $returnValue : base64_encode($returnValue);

            return response()->json([
                'success' => true,
                'pdf' => $pdf,
            ]);
        } catch (\SoapFault $e) {
            Log::error('SOAP Fault in customer sales entries', [
                'message' => $e->getMessage(),
                'fault_code' => $e->faultcode ?? 'N/A',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales entries: ' . $e->getMessage(),
            ], 500);
        } catch (\Throwable $th) {
            Log::error('Error fetching customer sales entries', [
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales entries: ' . $th->getMessage(),
            ], 500);
        }
    }
}
