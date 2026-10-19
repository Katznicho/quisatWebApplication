<?php

namespace App\Models;

use App\Services\CustomerSoapClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Sushi\Sushi;

class Invoice extends Model
{
    use Sushi;

    protected $fillable = [
        'invoiceNo',
        'customerNo',
        'postingDate',
        'startingDate',
        'endingDate',
        'description',
        'salesAmount',
        'discountAmount',
        'totalAmount',
    ];

    protected $schema = [
        'invoiceNo' => 'string',
        'customerNo' => 'string',
        'postingDate' => 'string',
        'startingDate' => 'string',
        'endingDate' => 'string',
        'description' => 'string',
        'salesAmount' => 'float',
        'discountAmount' => 'float',
        'totalAmount' => 'float',
    ];

    protected $casts = [
        'salesAmount' => 'float',
        'discountAmount' => 'float',
        'totalAmount' => 'float',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'invoiceNo';

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
            $data = $soap->json('GetCustomerInvoices', [
                'customerNo' => $customerNo,
            ]);

            return array_values(array_filter(array_map(
                fn ($item) => self::mapRow($item, $customerNo),
                $data
            )));
        } catch (\Throwable $th) {
            Log::error('Error fetching customer invoices', [
                'error' => $th->getMessage(),
            ]);

            return [];
        }
    }

    public static function findByNumber(string $invoiceNo): ?array
    {
        $invoiceNo = trim($invoiceNo);
        $customerNo = trim((string) session('customerNumber'));

        if ($invoiceNo === '' || $customerNo === '') {
            return null;
        }

        foreach ((new static)->getRows() as $row) {
            if (($row['invoiceNo'] ?? '') === $invoiceNo) {
                return $row;
            }
        }

        return null;
    }

    public static function mapRow(mixed $item, string $customerNo): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $invoiceNo = (string) CustomerSoapClient::pick($item, [
            'invoiceNo',
        ]);

        if ($invoiceNo === '') {
            return null;
        }

        return [
            'invoiceNo' => $invoiceNo,
            'customerNo' => (string) CustomerSoapClient::pick($item, [
                'customerNo',
            ], $customerNo),
            'postingDate' => (string) CustomerSoapClient::pick($item, [
                'postingDate',
            ]),
            'startingDate' => (string) CustomerSoapClient::pick($item, [
                'startingDate',
            ]),
            'endingDate' => (string) CustomerSoapClient::pick($item, [
                'endingDate',
            ]),
            'description' => (string) CustomerSoapClient::pick($item, [
                'description',
            ]),
            'salesAmount' => CustomerSoapClient::pickFloat($item, [
                'salesAmount',
            ]),
            'discountAmount' => CustomerSoapClient::pickFloat($item, [
                'discountAmount',
            ]),
            'totalAmount' => CustomerSoapClient::pickFloat($item, [
                'totalAmount',
            ]),
        ];
    }
}
