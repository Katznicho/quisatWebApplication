<?php

namespace App\Models;

use App\Services\CustomerSoapClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;
use SoapClient;
use stdClass;

class Account extends Model
{
    use HasFactory;
    use Sushi;

    protected $fillable = [
        'customerNo',
        'accountNo',
        'accountName',
        'category',
        'phoneNo',
        'email',
        'cardNo',
        'vehicleRegNo',
        'balance',
        'Blocked',
    ];

    protected $schema = [
        'customerNo' => 'string',
        'accountNo' => 'string',
        'accountName' => 'string',
        'category' => 'string',
        'phoneNo' => 'string',
        'email' => 'string',
        'cardNo' => 'string',
        'vehicleRegNo' => 'string',
        'balance' => 'float',
        'Blocked' => 'boolean',
    ];

    protected $casts = [
        'balance' => 'float',
        'Blocked' => 'boolean',
    ];

    protected function sushiShouldCache(): bool
    {
        return false;
    }

    public function getRows(): array
    {
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

        $params = new stdClass();
        $params->customerNo = trim($customerNo);
        $params->userName = trim($userName);

        $result = $service->GetCustomerAccounts($params);
        $data = json_decode($result->return_value, true) ?? [];

        if (! is_array($data)) {
            return [];
        }

        return array_values(array_map(function ($item) {
            if (! is_array($item)) {
                return [
                    'customerNo' => '',
                    'accountNo' => '',
                    'accountName' => '',
                    'category' => '',
                    'phoneNo' => '',
                    'email' => '',
                    'cardNo' => '',
                    'vehicleRegNo' => '',
                    'balance' => 0,
                    'Blocked' => false,
                ];
            }

            $blockedValue = $item['Blocked']
                ?? $item['blocked']
                ?? $item['isBlocked']
                ?? $item['blockedStatus']
                ?? null;

            $blocked = false;
            if (is_bool($blockedValue)) {
                $blocked = $blockedValue;
            } elseif (is_string($blockedValue)) {
                $blocked = in_array(strtolower($blockedValue), ['true', '1', 'yes', 'blocked'], true);
            } elseif (is_numeric($blockedValue)) {
                $blocked = (bool) $blockedValue;
            }

            return [
                'customerNo' => (string) CustomerSoapClient::pick($item, ['customerNo', 'CustomerNo', 'Customer_No']),
                'accountNo' => (string) CustomerSoapClient::pick($item, ['accountNo', 'AccountNo', 'Account_No']),
                'accountName' => (string) CustomerSoapClient::pick($item, ['accountName', 'AccountName', 'Account_Name']),
                'category' => (string) CustomerSoapClient::pick($item, [
                    'categoryName', 'CategoryName', 'category', 'Category',
                    'mpeCategory', 'MPECategory', 'MPE_Category', 'mpe_category',
                    'categoryCode', 'CategoryCode', 'Category_Code',
                ]),
                'phoneNo' => (string) CustomerSoapClient::pick($item, ['phoneNo', 'PhoneNo', 'Phone_No']),
                'email' => (string) CustomerSoapClient::pick($item, ['email', 'Email', 'E_Mail']),
                'cardNo' => (string) CustomerSoapClient::pick($item, ['cardNo', 'CardNo', 'Card_No']),
                'vehicleRegNo' => (string) CustomerSoapClient::pick($item, ['vehicleRegNo', 'VehicleRegNo', 'Vehicle_Reg_No']),
                'balance' => CustomerSoapClient::pickFloat($item, ['balance', 'Balance']),
                'Blocked' => $blocked,
            ];
        }, array_is_list($data) ? $data : [$data]));
    }
}

