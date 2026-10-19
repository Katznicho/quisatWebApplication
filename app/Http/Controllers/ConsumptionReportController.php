<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SoapClient;
use stdClass;

class ConsumptionReportController extends Controller
{
    public function index()
    {
        return view('reports.consumption');
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
            $dateDiff = $startDateObj->diff($endDateObj);
            $daysDiff = $dateDiff->days;

            if ($daysDiff > 365) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date range cannot exceed 365 days. Please select a shorter date range for better performance.',
                    'date_range_days' => $daysDiff,
                ], 400);
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $customerNo = session('customerNumber');
            $email = $request->input('email') ?: session('email');

            if (! $customerNo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer number not found in session. Please log in again.',
                ], 403);
            }

            if (! $email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found. Please update your profile email and try again.',
                ], 422);
            }

            $webServiceUrl = config('app.webService');
            $username = config('app.soapUsername');
            $password = config('app.soapPassword');

            if (! $webServiceUrl || ! $username || ! $password) {
                Log::error('Consumption report: SOAP configuration missing');

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
                Log::error('Consumption report: Failed to create SOAP client', [
                    'error' => $e->getMessage(),
                    'url' => $webServiceUrl,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to report service: ' . $e->getMessage(),
                ], 500);
            }

            $params = new stdClass();
            $params->customerNo = trim($customerNo);
            $params->email = trim($email);
            $params->startingDate = $startDate;
            $params->endingDate = $endDate;

            Log::info('Consumption report: calling SendConsumptionReport', [
                'customerNo' => $customerNo,
                'email' => $email,
                'startingDate' => $startDate,
                'endingDate' => $endDate,
            ]);

            try {
                $result = $service->SendConsumptionReport($params);
            } catch (\SoapFault $e) {
                Log::error('Consumption report: SendConsumptionReport SOAP fault', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to submit report request: ' . $e->getMessage(),
                ], 500);
            }

            $returnValue = $result->return_value ?? null;
            $success = $returnValue === true || strtolower((string) $returnValue) === 'true' || (string) $returnValue === '1';

            Log::info('Consumption report: SendConsumptionReport response', [
                'raw_return_value' => $returnValue,
                'return_value_type' => gettype($returnValue),
                'success' => $success,
            ]);

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report request was not accepted. Please confirm your email and try again.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Report request submitted successfully. You will receive the report by email once it is generated.',
            ]);
        } catch (\SoapFault $e) {
            Log::error('SOAP Fault in consumption report generation', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'fault_code' => $e->faultcode ?? 'N/A',
                'fault_string' => $e->faultstring ?? 'N/A',
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report request: ' . $e->getMessage(),
            ], 500);
        } catch (\Throwable $th) {
            Log::error('Error generating consumption report', [
                'error' => $th->getMessage(),
                'error_class' => get_class($th),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report request: ' . $th->getMessage(),
            ], 500);
        }
    }
}

