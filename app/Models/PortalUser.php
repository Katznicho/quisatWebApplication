<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;
use Illuminate\Support\Arr;
use SoapClient;
use stdClass;

class PortalUser extends Model
{
    use Sushi;

    protected $fillable = [
        'customerNo',
        'userName',
        'fullName',
        'phoneNo',
        'email',
        'accountType',
        'blocked',
    ];

    protected $schema = [
        'customerNo' => 'string',
        'userName' => 'string',
        'fullName' => 'string',
        'phoneNo' => 'string',
        'email' => 'string',
        'accountType' => 'string',
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
            $locationCode = session('locationcode');

            $params = new stdClass();
            $params->customerNo = trim((string) $customerNo);
            if ($locationCode) {
                $params->locationCode = trim($locationCode);
            }

            $result = $service->GetCustomerUsers($params);
            $data = json_decode($result->return_value ?? '[]', true);

            if (! is_array($data) || $data === []) {
                return [];
            }

            return Arr::map($data, function (array $item) {
                return Arr::only(
                    $item,
                    [
                        'customerNo',
                        'userName',
                        'fullName',
                        'phoneNo',
                        'email',
                        'accountType',
                        'blocked',
                    ]
                );
            });
        } catch (\Throwable $th) {
            return [];
        }
    }
}

