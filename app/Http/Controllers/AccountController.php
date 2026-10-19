<?php

namespace App\Http\Controllers;

use App\Services\CustomerSoapClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SoapClient;
use stdClass;

class AccountController extends Controller
{
    public function index()
    {
        return view('accounts.index');
    }

    public function create()
    {
        try {
            $docNo = session('customerNumber');
            $userName = session('userName');

            $webServiceUrl = config('app.webService');
            $username = config('app.soapUsername');
            $password = config('app.soapPassword');
            $options = [
                'login' => $username,
                'password' => $password,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $params = new stdClass();
            $params->customerNo = trim($docNo);
            $params->userName = trim($userName);

            $result = $service->CreateCustomerAccount($params);

            if ($result->return_value) {
                $accountNo = $result->return_value;

                return redirect()->route('accounts.edit', ['account' => $accountNo])
                    ->with('success', 'Account created successfully');
            }

            return back()->with('error', 'Failed to create account');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function show(string $accountNo)
    {
        return redirect()->route('accounts.edit', ['account' => $accountNo, 'readonly' => true]);
    }

    public function edit(string $accountNo, Request $request)
    {
        try {
            $docNo = session('customerNumber');
            $baseUrl = rtrim(config('app.odata'), '/');
            $filter = "Customer_No eq '{$docNo}' and Account_No eq '{$accountNo}'";
            $loggedUrl = $baseUrl . "/CustomerAccountAPI?\$filter=" . urlencode($filter);

            Log::info('Account edit: OData URL', [
                'url' => $loggedUrl,
            ]);

            $httpClient = Http::withOptions([
                // TEMP: relax SSL + force IPv4 to avoid local cURL issues
                'verify' => false,
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ])->withBasicAuth(
                config('app.soapUsername'),
                config('app.soapPassword')
            )
                ->timeout(10)
                ->connectTimeout(5);

            $response = $httpClient->get($baseUrl . '/CustomerAccountAPI', [
                '$filter' => $filter,
            ]);

            if (! $response->successful()) {
                return back()->with('error', 'Failed to fetch account details');
            }

            $data = json_decode($response->body());
            $account = $data->value[0] ?? null;

            if (! $account) {
                abort(404, 'Account not found');
            }

            $customerNo = $account->Customer_No ?? $docNo;

            $cardLocations = null;
            try {
                $filtersToTry = [
                    "Customer_No eq '{$customerNo}' and Account_No eq '{$accountNo}'",
                    "Account_No eq '{$accountNo}'",
                    "Customer_No eq '{$customerNo}'",
                ];

                foreach ($filtersToTry as $filter) {
                    $cardLocationsUrl = $baseUrl . "/loyaltyCardLocations?\$filter=" . urlencode($filter);
                    $cardLocationsResponse = Http::withBasicAuth(
                        config('app.soapUsername'),
                        config('app.soapPassword')
                    )
                        ->timeout(20)
                        ->connectTimeout(10)
                        ->get($cardLocationsUrl);

                    if ($cardLocationsResponse->successful()) {
                        $cardLocationsData = json_decode($cardLocationsResponse->body());
                        if (isset($cardLocationsData->value) && ! empty($cardLocationsData->value)) {
                            $cardLocations = $cardLocationsData->value;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Error fetching card locations', ['error' => $e->getMessage()]);
            }

            // Temporarily disable expensive card limits and usage-days lookups
            // to avoid timeouts while editing accounts.
            $cardLimits = null;
            $usageDays = null;

            $readonly = $request->boolean('readonly', false);

            $graphData = [
                'value' => 0,
                'max' => 0,
                'remaining' => 0,
                'percent' => 0,
                'label' => 'Account usage',
                'available' => false,
            ];

            try {
                $graphData = app(CustomerSoapClient::class)->accountGraph(
                    (string) $customerNo,
                    (string) $accountNo
                );
            } catch (\Throwable $e) {
                Log::warning('Account graph: SOAP failed', [
                    'accountNo' => $accountNo,
                    'error' => $e->getMessage(),
                ]);
            }

            return view('accounts.edit', compact(
                'account',
                'cardLocations',
                'cardLimits',
                'usageDays',
                'readonly',
                'graphData'
            ));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function updateAccountDetails(Request $request)
    {
        try {
            $request->validate([
                'patch_key' => 'required',
                'field_name' => 'required',
                'field_value' => 'required',
                'field_type' => 'required',
            ]);

            $key = $request->patch_key;
            $pathUrl = config('app.odata') . "/CustomerAccountAPI" . '(' . $key . ')';

            $data = [
                $request->field_name => $request->field_type === 'number'
                    ? (int) $request->field_value
                    : $request->field_value,
            ];

            $res = GeneralController::sendPatchRequest($pathUrl, $data);

            if ($res['status'] === 'success') {
                return response()->json(['success' => true, 'message' => 'The user has been updated successfully'], 200);
            }

            return response()->json(['success' => false, 'message' => $res['message']], 500);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function updateCardLocation(Request $request)
    {
        try {
            $request->validate([
                'patch_key' => 'required',
                'field_name' => 'required',
                'field_value' => 'required',
                'field_type' => 'required',
            ]);

            $patchKey = $request->patch_key;
            $pathUrl = config('app.odata') . "/loyaltyCardLocations(" . $patchKey . ")";

            $data = [
                $request->field_name => $request->field_type === 'boolean'
                    ? (bool) $request->field_value
                    : $request->field_value,
            ];

            $res = GeneralController::sendPatchRequest($pathUrl, $data);

            if ($res['status'] === 'success') {
                return response()->json(['success' => true, 'message' => 'Field updated successfully'], 200);
            }

            return response()->json(['success' => false, 'message' => $res['message']], 500);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function updateUsageDay(Request $request)
    {
        try {
            $request->validate([
                'patch_key' => 'required',
                'field_name' => 'required',
                'field_value' => 'required',
                'field_type' => 'required',
            ]);

            $patchKey = $request->patch_key;
            $pathUrl = config('app.odata') . "/loyaltyCardUsageDays(" . $patchKey . ")";

            $data = [
                $request->field_name => $request->field_type === 'boolean'
                    ? (bool) $request->field_value
                    : $request->field_value,
            ];

            $res = GeneralController::sendPatchRequest($pathUrl, $data);

            if ($res['status'] === 'success') {
                return response()->json(['success' => true, 'message' => 'Field updated successfully'], 200);
            }

            return response()->json(['success' => false, 'message' => $res['message']], 500);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function getCardLimits(string $accountNo, Request $request)
    {
        try {
            $customerNo = $request->query('customerNo') ?: session('customerNumber');
            $cardNo = $request->query('cardNo');

            if (! $customerNo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer number not provided.',
                ], 400);
            }

            $baseUrl = rtrim(config('app.odata'), '/');

            $cardLimits = null;
            $endpointsToTry = [
                'loyaltyCardLimits',
                'loyaltyCardAccount',
                'loyaltyCardAccounts',
                'loyaltyCardAccountLimit',
                'loyaltyCardAccountLimits',
                'loyaltyCardLimit',
                'LoyaltyCardAccount',
                'LoyaltyCardAccounts',
                'LoyaltyCardAccountLimit',
                'LoyaltyCardAccountLimits',
                'CardAccount',
                'CardAccountLimit',
                'CardAccountLimits',
            ];

            $cardLimitsFound = false;

            foreach ($endpointsToTry as $endpoint) {
                if ($cardLimitsFound) {
                    break;
                }

                $filtersToTry = [
                    "Account_No eq '{$accountNo}'",
                    "Customer_No eq '{$customerNo}' and Account_No eq '{$accountNo}'",
                    "Customer_No eq '{$customerNo}'",
                ];

                if ($cardNo) {
                    $filtersToTry[] = "Card_No eq '{$cardNo}'";
                    $filtersToTry[] = "Account_No eq '{$accountNo}' and Card_No eq '{$cardNo}'";
                }

                $filtersToTry[] = null;

                foreach ($filtersToTry as $filter) {
                    if ($cardLimitsFound) {
                        break;
                    }

                    try {
                        $cardLimitsUrl = $baseUrl . "/{$endpoint}";
                        if ($filter) {
                            $cardLimitsUrl .= "?\$filter=" . urlencode($filter);
                        }

                        Log::info('Account card limits: attempting endpoint', [
                            'endpoint' => $endpoint,
                            'url' => $cardLimitsUrl,
                        ]);

                        $response = Http::withBasicAuth(
                            config('app.soapUsername'),
                            config('app.soapPassword')
                        )
                            ->timeout(10)
                            ->connectTimeout(5)
                            ->get($cardLimitsUrl);

                        if (! $response->successful()) {
                            Log::warning('Account card limits: endpoint failed', [
                                'endpoint' => $endpoint,
                                'status' => $response->status(),
                            ]);

                            continue;
                        }

                        $data = json_decode($response->body());
                        if (! isset($data->value)) {
                            continue;
                        }

                        if (is_array($data->value) && ! empty($data->value)) {
                            $cardLimits = $data->value;
                        } elseif (is_object($data->value) && ! empty((array) $data->value)) {
                            $cardLimits = [$data->value];
                        }

                        if ($cardLimits) {
                            $cardLimitsFound = true;
                            break;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Account card limits: failed endpoint {$endpoint}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if (! $cardLimits) {
                return response()->json([
                    'success' => true,
                    'no_data' => true,
                    'data' => [],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $cardLimits,
            ]);
        } catch (\Throwable $th) {
            Log::error('Account card limits error', [
                'error' => $th->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getUsageDays(string $accountNo)
    {
        try {
            $baseUrl = rtrim(config('app.odata'), '/');

            $url = $baseUrl . "/loyaltyCardUsageDays?\$filter=Account_No eq '" . $accountNo . "'";

            Log::info('Account usage days: request', [
                'url' => $url,
            ]);

            $response = Http::withBasicAuth(
                config('app.soapUsername'),
                config('app.soapPassword')
            )
                ->timeout(10)
                ->connectTimeout(5)
                ->get($url);

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch usage days.',
                    'status' => $response->status(),
                ], 500);
            }

            $data = json_decode($response->body());
            $usageDays = isset($data->value) && ! empty($data->value) ? $data->value : [];

            if (empty($usageDays)) {
                return response()->json([
                    'success' => true,
                    'no_data' => true,
                    'data' => [],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $usageDays,
            ]);
        } catch (\Throwable $th) {
            Log::error('Account usage days error', [
                'error' => $th->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function blockAccount(Request $request)
    {
        try {
            $request->validate([
                'accountNo' => 'required|string',
                'reason' => 'required|string|min:3',
            ]);

            $customerNo = session('customerNumber');
            $accountNo = trim($request->accountNo);

            $webServiceUrl = config('app.webService');
            $username = config('app.soapUsername');
            $password = config('app.soapPassword');
            $options = [
                'login' => $username,
                'password' => $password,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $params = new stdClass();
            $params->customerNo = $customerNo;
            $params->accountNo = $accountNo;
            $params->reason = $request->reason;

            $result = $service->BlockAccount($params);

            if ($result->return_value) {
                return response()->json([
                    'success' => true,
                    'message' => 'Account has been blocked successfully',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to block account',
            ], 500);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function assignCards(Request $request)
    {
        try {
            $customerNo = session('customerNumber');

            $request->validate([
                'accountNo' => 'required|string',
                'cardNo' => 'required|string',
            ]);

            $webServiceUrl = config('app.webService');
            $username = config('app.soapUsername');
            $password = config('app.soapPassword');
            $options = [
                'login' => $username,
                'password' => $password,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $params = new stdClass();
            $params->customerNo = trim($customerNo);
            $params->accountNo = trim($request->accountNo);
            $params->cardNo = trim($request->cardNo);

            $result = $service->AssignCard($params);

            if ($result->return_value) {
                return response()->json([
                    'success' => true,
                    'message' => 'Card assigned successfully',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign card',
            ], 500);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function graph(string $accountNo)
    {
        try {
            $customerNo = session('customerNumber');

            if (! $customerNo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer number not found in session.',
                ], 403);
            }

            $graphData = app(CustomerSoapClient::class)->accountGraph(
                (string) $customerNo,
                (string) $accountNo
            );

            return response()->json([
                'success' => true,
                'data' => $graphData,
            ]);
        } catch (\Throwable $th) {
            Log::error('Account graph error', [
                'accountNo' => $accountNo,
                'error' => $th->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}

