<?php

namespace App\Models;

use App\Services\CustomerSoapClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Sushi\Sushi;

class MpeCategory extends Model
{
    use Sushi;

    protected $fillable = [
        'code',
        'name',
        'description',
        'customerNo',
    ];

    protected $schema = [
        'code' => 'string',
        'name' => 'string',
        'description' => 'string',
        'customerNo' => 'string',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'code';

    protected function sushiShouldCache(): bool
    {
        return false;
    }

    public function getRows(): array
    {
        try {
            $customerNo = trim((string) session('customerNumber'));

            if ($customerNo === '') {
                return [];
            }

            $soap = app(CustomerSoapClient::class);
            $data = $soap->json('GetCustomerMPECategories', [
                'customerNo' => $customerNo,
            ]);

            return array_values(array_filter(array_map(function ($item) use ($customerNo) {
                if (! is_array($item)) {
                    return null;
                }

                $code = (string) CustomerSoapClient::pick($item, [
                    'code', 'Code', 'categoryCode', 'CategoryCode', 'Category_Code',
                ]);

                if ($code === '') {
                    return null;
                }

                return [
                    'code' => $code,
                    'name' => (string) CustomerSoapClient::pick($item, [
                        'name', 'Name', 'categoryName', 'CategoryName',
                    ]),
                    'description' => (string) CustomerSoapClient::pick($item, [
                        'description', 'Description',
                    ]),
                    'customerNo' => (string) CustomerSoapClient::pick($item, [
                        'customerNo', 'CustomerNo', 'Customer_No',
                    ], $customerNo),
                ];
            }, $data)));
        } catch (\Throwable $th) {
            Log::error('Error fetching MPE categories', [
                'error' => $th->getMessage(),
            ]);

            return [];
        }
    }
}
