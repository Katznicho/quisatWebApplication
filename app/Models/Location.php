<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;
use SoapClient;

class Location extends Model
{
    use Sushi;

    protected $fillable = [
        'Code',
        'Name',
        'district',
        'LocationType',
        'email',
        'phoneNumber',
        'latitude',
        'longitude',
    ];

    protected $schema = [
        'Code' => 'string',
        'Name' => 'string',
        'district' => 'string',
        'LocationType' => 'string',
        'email' => 'string',
        'phoneNumber' => 'string',
        'latitude' => 'string',
        'longitude' => 'string',
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

        $result = $service->GetLocations();
        $data = json_decode($result->return_value, true) ?? [];

        return array_values(array_map(function (array $item) {
            return [
                'Code' => (string) ($item['Code'] ?? $item['code'] ?? ''),
                'Name' => (string) ($item['Name'] ?? $item['name'] ?? ''),
                'district' => (string) ($item['district'] ?? $item['District'] ?? ''),
                'LocationType' => (string) ($item['LocationType'] ?? $item['locationType'] ?? ''),
                'email' => (string) ($item['email'] ?? $item['Email'] ?? ''),
                'phoneNumber' => (string) ($item['phoneNumber'] ?? $item['PhoneNumber'] ?? ''),
                'latitude' => (string) ($item['latitude'] ?? $item['Latitude'] ?? ''),
                'longitude' => (string) ($item['longitude'] ?? $item['Longitude'] ?? ''),
            ];
        }, $data));
    }
}

