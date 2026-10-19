<?php

namespace App\Http\Controllers\Api\Kcb;

use App\Http\Controllers\Controller;
use App\Models\KcbTransaction;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BillNotificationController extends Controller
{

    /**
     * Handle bill notification request from KCB
     * 
     * KCB sends payment notifications to this endpoint
     * We receive and save all data from KCB, then return a response
     * 
     * @bodyParam transactionReference string required Example: FT00026252
     * @bodyParam requestId string required Example: c7d702cb-6b5f-4fa6-8b57-436d0f789017
     * @bodyParam channelCode string Example: 202
     * @bodyParam timestamp string required Format: YYYYMMDDHHmmss Example: 20211111030050
     * @bodyParam transactionAmount string required Example: 100.00
     * @bodyParam currency string required Example: KES
     * @bodyParam customerReference string required Example: SAMPLE001
     * @bodyParam customerName string Example: John Doe
     * @bodyParam customerMobileNumber string Example: 254711111111
     * @bodyParam balance string Example: 100000.00
     * @bodyParam narration string required Example: Payment for goods
     * @bodyParam creditAccountIdentifier string required Example: JD001
     * @bodyParam organizationShortCode string required Example: 777777
     * @bodyParam tillNumber string Example: 150150
     * 
     * @response 200 {
     *   "transactionID": "c7d702cb-6b5f-4fa6-8b57-436d0f789017",
     *   "statusCode": 0,
     *   "statusMessage": "Notification received"
     * }
     * 
     * @response 400 {
     *   "transactionID": "c7d702cb-6b5f-4fa6-8b57-436d0f789017",
     *   "statusCode": 1,
     *   "statusMessage": "Validation failed: The transactionReference field is required."
     * }
     * 
     * POST /api/kcb/bill-notification
     */
    public function notifyBill(Request $request)
    {
        try {
            // Validate required fields from KCB request
            $validator = Validator::make($request->all(), [
                'transactionReference' => 'required|string',
                'requestId' => 'required|string',
                'timestamp' => 'required|string',
                'transactionAmount' => 'required',
                'currency' => 'required|string|size:3',
                'customerReference' => 'required|string',
                'narration' => 'required|string',
                'creditAccountIdentifier' => 'required|string',
                'organizationShortCode' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'transactionID' => $request->input('requestId', ''),
                    'statusCode' => 1,
                    'statusMessage' => 'Validation failed: ' . $validator->errors()->first(),
                ], 400);
            }

            $requestId = $request->input('requestId');
            $transactionReference = $request->input('transactionReference');

            // Log all data received from KCB
            Log::info('KCB Bill Notification Request - Received from KCB', [
                'all_data' => $request->all(),
                'requestId' => $requestId,
                'transactionReference' => $transactionReference
            ]);

            // Parse and validate numeric fields
            $transactionAmount = $this->parseDecimal($request->input('transactionAmount'), 'transactionAmount');
            $balance = $this->parseDecimal($request->input('balance'), 'balance', true);

            if ($transactionAmount === false) {
                return response()->json([
                    'transactionID' => $requestId,
                    'statusCode' => 1,
                    'statusMessage' => 'Invalid transaction amount format',
                ], 400);
            }

            // Parse timestamp (YYYYMMDDHHmmss) from KCB
            $timestamp = $request->input('timestamp');
            $parsedTimestamp = null;
            if ($timestamp && strlen($timestamp) === 14) {
                try {
                    $parsedTimestamp = Carbon::createFromFormat('YmdHis', $timestamp);
                } catch (\Exception $e) {
                    Log::warning('Invalid timestamp format in KCB Bill Notification', [
                        'timestamp' => $timestamp,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Save all data received from KCB to our database
            // Note: For bill notifications, KCB doesn't send organizationReference,
            // so we use organizationShortCode as organization_reference
            $transaction = KcbTransaction::create([
                'request_id' => $requestId,
                'transaction_reference' => $transactionReference,
                'customer_reference' => $request->input('customerReference'),
                'organization_reference' => $request->input('organizationReference', $request->input('organizationShortCode')),
                'organization_short_code' => $request->input('organizationShortCode'),
                'channel_code' => $request->input('channelCode'),
                'till_number' => $request->input('tillNumber'),
                'transaction_amount' => $transactionAmount,
                'currency' => $request->input('currency'),
                'customer_name' => $request->input('customerName'),
                'customer_mobile_number' => $request->input('customerMobileNumber'),
                'balance' => $balance,
                'narration' => $request->input('narration'),
                'credit_account_identifier' => $request->input('creditAccountIdentifier'),
                'transaction_timestamp' => $parsedTimestamp,
                'transaction_type' => 'notification',
                'raw_request' => json_encode($request->all()),
                'status_code' => '0',
                'status_message' => 'Notification received',
            ]);

            // TODO: Implement your business logic to process the payment
            // All data from KCB has been saved to the database above
            // You can now process the payment, update balances, send notifications, etc.

            // After saving, send a callback to SAP (or other downstream system)
            $this->sendSapCallback($request, $transaction, $parsedTimestamp);

            $response = [
                'transactionID' => $requestId,
                'statusCode' => 0,
                'statusMessage' => 'Notification received',
            ];

            // Update transaction with response we sent back to KCB
            $transaction->update([
                'raw_response' => json_encode($response),
            ]);

            return response()->json($response);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error while saving KCB Bill Notification', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'transactionID' => $request->input('requestId', ''),
                'statusCode' => 1,
                'statusMessage' => 'Failed to process notification: Invalid data format',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error while processing KCB Bill Notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'transactionID' => $request->input('requestId', ''),
                'statusCode' => 1,
                'statusMessage' => 'Internal server error while processing notification',
            ], 500);
        }
    }

    /**
     * Send a normalized callback payload to the business callback_url (e.g. SAP)
     * so downstream systems receive a bank-agnostic notification.
     */
    protected function sendSapCallback(Request $request, KcbTransaction $transaction, ?Carbon $parsedTimestamp = null): void
    {
        try {
            // For now we always work with a single GreensWell tenant and a single SAP callback URL.
            $business = Bill::where('customer_reference', $request->input('customerReference'))
                ->where('organization_reference', $request->input('organizationReference', $request->input('organizationShortCode')))
                ->first()
                ?->business
                ?? \App\Models\Business::first();

            if (! $business || empty($business->callback_url)) {
                Log::info('No callback_url configured for KCB notification', [
                    'customerReference' => $request->input('customerReference'),
                ]);
                return;
            }

            $callbackUrl = $business->callback_url;

            // Build payload for SAP as per agreed format
            $payload = [
                'provider' => 'KCB',

                'transaction' => [
                    'internalId' => $transaction->id,
                    'uuid' => $transaction->uuid,
                    'requestId' => $transaction->request_id,
                    'transactionReference' => $transaction->transaction_reference,
                    'narration' => $transaction->narration,
                    'timestamp' => optional($parsedTimestamp)->toIso8601String(),
                ],

                'amount' => [
                    'transactionAmount' => (float) $transaction->transaction_amount,
                    'currency' => $transaction->currency,
                    'balance' => $transaction->balance !== null ? (float) $transaction->balance : null,
                ],

                'party' => [
                    'customerReference' => $transaction->customer_reference,
                    'customerName' => $transaction->customer_name,
                    'customerMobileNumber' => $transaction->customer_mobile_number,
                    'creditAccountIdentifier' => $transaction->credit_account_identifier,
                ],
            ];

            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Provider' => 'KCB',
                    'X-Event-Type' => 'bill_notification',
                ])
                ->post($callbackUrl, $payload);

            if ($response->failed()) {
                Log::warning('Failed to deliver KCB notification callback to SAP', [
                    'callback_url' => $callbackUrl,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } else {
                Log::info('Delivered KCB notification callback to SAP successfully', [
                    'callback_url' => $callbackUrl,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            // Never break the KCB notification flow because of callback issues
            Log::error('Error while sending KCB notification callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Parse decimal value from request input
     * 
     * @param mixed $value
     * @param string $fieldName
     * @param bool $nullable
     * @return float|null|false Returns false on invalid format
     */
    private function parseDecimal($value, string $fieldName, bool $nullable = false)
    {
        // Handle null/empty values
        if (empty($value)) {
            return $nullable ? null : false;
        }

        // If it's already numeric, return it
        if (is_numeric($value)) {
            return (float) $value;
        }

        // If it's a string, try to extract numeric value
        if (is_string($value)) {
            // Remove any non-numeric characters except decimal point and minus sign
            $cleaned = preg_replace('/[^0-9.-]/', '', $value);
            
            // Check if the cleaned value is numeric
            if ($cleaned !== '' && is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }

        // Invalid format
        Log::warning("Invalid decimal format for field: {$fieldName}", [
            'value' => $value,
            'type' => gettype($value)
        ]);

        return $nullable ? null : false;
    }
}
