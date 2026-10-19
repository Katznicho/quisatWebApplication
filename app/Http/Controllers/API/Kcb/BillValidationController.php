<?php

namespace App\Http\Controllers\Api\Kcb;

use App\Http\Controllers\Controller;
use App\Models\KcbTransaction;
use App\Models\Bill;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BillValidationController extends Controller
{

    /**
     * Handle bill validation request from KCB
     * 
     * KCB sends validation requests to this endpoint
     * We receive and save all data from KCB, then return a response
     * 
     * @bodyParam requestId string required Example: d115245e-9604-49de-9436-9fdcb539871f
     * @bodyParam customerReference string required Example: SAMPLE001
     * @bodyParam organizationReference string required Example: 777777
     * 
     * @response 200 {
     *   "transactionID": "d115245e-9604-49de-9436-9fdcb539871f",
     *   "statusCode": 0,
     *   "statusMessage": "Success",
     *   "CustomerName": "John Doe",
     *   "billAmount": 100.00,
     *   "currency": "KES",
     *   "billType": "PARTIAL",
     *   "creditAccountIdentifier": "ACC123456"
     * }
     * 
     * @response 400 {
     *   "transactionID": "d115245e-9604-49de-9436-9fdcb539871f",
     *   "statusCode": 1,
         *   "statusMessage": "Validation failed: The requestId field is required.",
         *   "CustomerName": "",
         *   "billAmount": 0.00,
         *   "currency": "KES",
         *   "billType": "PARTIAL",
         *   "creditAccountIdentifier": ""
     * }
     * 
     * POST /api/kcb/bill-validation
     */
    public function validateBill(Request $request)
    {
        try {
            // Validate required fields from KCB request
            $validator = Validator::make($request->all(), [
                'requestId' => 'required|string',
                'customerReference' => 'required|string',
                'organizationReference' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'transactionID' => $request->input('requestId', ''),
                    'statusCode' => 1,
                    'statusMessage' => 'Validation failed: ' . $validator->errors()->first(),
                    'CustomerName' => '',
                    'billAmount' => 0.00,
                    'currency' => 'KES',
                    'billType' => 'PARTIAL',
                    'creditAccountIdentifier' => ''
                ], 400);
            }

            $requestId = $request->input('requestId');
            $customerReference = $request->input('customerReference');
            $organizationReference = $request->input('organizationReference');

            // Log all data received from KCB
            Log::info('KCB Bill Validation Request - Received from KCB', [
                'all_data' => $request->all(),
                'requestId' => $requestId,
                'customerReference' => $customerReference,
                'organizationReference' => $organizationReference
            ]);

            // Save all data received from KCB to our database
            $transaction = KcbTransaction::create([
                'request_id' => $requestId,
                'customer_reference' => $customerReference,
                'organization_reference' => $organizationReference,
                'transaction_type' => 'validation',
                'raw_request' => json_encode($request->all()),
                'status_code' => '0',
                'status_message' => 'Success',
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error while saving KCB Bill Validation', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'transactionID' => $request->input('requestId', ''),
                'statusCode' => 1,
                'statusMessage' => 'Failed to process validation: Invalid data format',
                'CustomerName' => '',
                'billAmount' => 0.00,
                'currency' => 'KES',
                'billType' => 'PARTIAL',
                'creditAccountIdentifier' => ''
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error while processing KCB Bill Validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'transactionID' => $request->input('requestId', ''),
                'statusCode' => 1,
                'statusMessage' => 'Internal server error while processing validation',
                'CustomerName' => '',
                'billAmount' => 0.00,
                'currency' => 'KES',
                'billType' => 'PARTIAL',
                'creditAccountIdentifier' => ''
            ], 500);
        }

        // Look up customer and bill using the provided unique identifiers
        $customer = Customer::where('customer_reference', $customerReference)->first();

        $bill = Bill::where('customer_reference', $customerReference)
            ->where('organization_reference', $organizationReference)
            ->first();

        if (!$customer || !$bill) {
            $statusMessage = !$customer
                ? 'Validation failed: Unknown customerReference.'
                : 'Validation failed: No bill found for provided identifiers.';

            $response = [
                'transactionID' => $requestId,
                'statusCode' => 1,
                'statusMessage' => $statusMessage,
                'CustomerName' => '',
                'billAmount' => '0.00',
                'currency' => 'KES',
                'billType' => 'PARTIAL',
                'creditAccountIdentifier' => '',
            ];

            $transaction->update([
                'status_code' => '1',
                'status_message' => $statusMessage,
                'raw_response' => json_encode($response),
            ]);

            // For business-level validation failures, return 200 with statusCode = 1
            return response()->json($response);
        }

        // Successful validation: return actual customer and bill details
        $customerName = $bill->customer_name ?? $customer->name ?? 'Customer';
        $billAmount = $bill->amount !== null ? number_format((float) $bill->amount, 2, '.', '') : '0.00';
        $currency = $bill->currency ?? 'KES';
        $creditAccountIdentifier = $bill->credit_account_identifier ?? '';

        $response = [
            'transactionID' => $requestId,
            'statusCode' => 0,
            'statusMessage' => 'Success',
            'CustomerName' => $customerName,
            'billAmount' => $billAmount,
            'currency' => $currency,
            'billType' => 'PARTIAL',
            'creditAccountIdentifier' => $creditAccountIdentifier,
        ];

        // Update transaction with response we sent back to KCB
        $transaction->update([
            'status_code' => '0',
            'status_message' => 'Success',
            'raw_response' => json_encode($response),
        ]);

        return response()->json($response);
    }
}
