<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Sushi\Sushi;
use SoapClient;
use stdClass;

class Region extends Model
{
    use HasFactory;
    use Sushi;

    protected $fillable = [
        'customerNo',
        'code',
        'name',
        'blocked',
    ];

    protected $schema = [
        'customerNo' => 'string',
        'code' => 'string',
        'name' => 'string',
        'blocked' => 'boolean',
    ];

    protected $casts = [
        'blocked' => 'boolean',
    ];

    public function getRows(): array
    {
        try {
            $webServiceUrl = config('app.webService');
            $username = config('app.soapUsername');
            $password = config('app.soapPassword');
            $options = [
                'login' => $username,
                'password' => $password,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $customerNo = session('customerNumber');
            $userName = session('userName');

            $methodsToTry = ['GetRegions', 'GetCustomerRegions', 'GetRegionsList'];

            foreach ($methodsToTry as $methodName) {
                $paramCombinations = [];

                if (!empty($customerNo)) {
                    $paramCombinations[] = ['customerNo' => trim($customerNo)];
                }

                if (!empty($userName)) {
                    $paramCombinations[] = ['userName' => trim($userName)];
                }

                if (!empty($customerNo) && !empty($userName)) {
                    $paramCombinations[] = [
                        'customerNo' => trim($customerNo),
                        'userName' => trim($userName),
                    ];
                }

                $paramCombinations[] = [];

                foreach ($paramCombinations as $paramSet) {
                    $params = new stdClass();
                    foreach ($paramSet as $key => $value) {
                        $params->$key = $value;
                    }

                    try {
                        $result = $service->$methodName($params);

                        if (isset($result->return_value) && !empty($result->return_value)) {
                            $data = json_decode($result->return_value, true);

                            if (is_array($data) && count($data) > 0) {
                                return Arr::map($data, function (array $item) {
                                    return Arr::only(
                                        $item,
                                        [
                                            'customerNo',
                                            'code',
                                            'name',
                                            'blocked',
                                        ]
                                    );
                                });
                            }
                        }
                    } catch (\SoapFault $e) {
                        Log::warning("Region SOAP method {$methodName} failed", [
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }
                }
            }

            return [];
        } catch (\Throwable $th) {
            Log::error('Error fetching regions', [
                'error' => $th->getMessage(),
            ]);

            return [];
        }
    }
}

