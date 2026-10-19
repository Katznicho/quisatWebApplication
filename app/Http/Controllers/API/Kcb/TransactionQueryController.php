<?php

namespace App\Http\Controllers\Api\Kcb;

use App\Http\Controllers\Controller;
use App\Models\KcbTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionQueryController extends Controller
{
    /**
     * Query transaction by FT reference or customer reference
     * 
     * @urlParam reference string required The transaction reference (FT reference) or customer reference. Example: FT00026252
     * 
     * @response 200 {
     *   "creditAccountIdentifier": "JD001",
     *   "transactionReference": "FT00026252",
     *   "organizationShortCode": "777777",
     *   "customerReference": "SAMPLE001",
     *   "transactionAmount": "100.00",
     *   "currency": "KES",
     *   "statusMessage": "Notification received",
     *   "customerName": "John Doe",
     *   "statusCode": 0,
     *   "channelCode": "202",
     *   "timestamp": "20211111030050"
     * }
     * 
     * @response 404 {
     *   "statusCode": 1,
     *   "statusMessage": "Transaction not found"
     * }
     * 
     * GET /kcb/transaction/query/{reference}
     */
    public function queryTransaction(Request $request, $reference)
    {
        Log::info('KCB Transaction Query Request', ['reference' => $reference]);

        // Try to find by transaction reference first, then by customer reference
        $transaction = KcbTransaction::where('transaction_reference', $reference)
            ->orWhere('customer_reference', $reference)
            ->where('transaction_type', 'notification')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$transaction) {
            return response()->json([
                'statusCode' => 1,
                'statusMessage' => 'Transaction not found',
            ], 404);
        }

        // Format timestamp as YYYYMMDDHHmmss
        $timestamp = $transaction->transaction_timestamp 
            ? $transaction->transaction_timestamp->format('YmdHis')
            : $transaction->created_at->format('YmdHis');

        $response = [
            'creditAccountIdentifier' => $transaction->credit_account_identifier ?? '',
            'transactionReference' => $transaction->transaction_reference ?? '',
            'organizationShortCode' => $transaction->organization_short_code ?? '',
            'customerReference' => $transaction->customer_reference,
            'transactionAmount' => $transaction->transaction_amount ? number_format($transaction->transaction_amount, 2, '.', '') : '0.00',
            'currency' => $transaction->currency ?? 'KES',
            'statusMessage' => $transaction->status_message ?? 'Success',
            'customerName' => $transaction->customer_name ?? '',
            'statusCode' => (int) $transaction->status_code,
            'channelCode' => $transaction->channel_code ?? '',
            'timestamp' => $timestamp,
        ];

        // Log the query
        KcbTransaction::create([
            'request_id' => 'query-' . $reference . '-' . now()->timestamp,
            'customer_reference' => $transaction->customer_reference,
            'organization_reference' => $transaction->organization_reference ?? '777777',
            'transaction_reference' => $transaction->transaction_reference,
            'transaction_type' => 'query',
            'raw_request' => json_encode(['reference' => $reference]),
            'raw_response' => json_encode($response),
            'status_code' => '0',
            'status_message' => 'Query successful',
        ]);

        return response()->json($response);
    }
}
