<?php

namespace App\Http\Controllers\Api\Sap;

use App\Http\Controllers\Controller;
use App\Models\Customer;

class MasterDataController extends Controller
{
    /**
     * Customer master data for SAP.
     *
     * Returns the list of customer records in the format agreed with SAP.
     *
     * @group SAP Integration
     *
     * @response 200 {
     *   "code": "GWC0001",
     *   "name": "Daniel Johnnes",
     *   "phoneNo": "",
     *   "email": "daniel@email.com",
     *   "address": "Plot 7145, Block 203, Ntinda, Nairobi"
     * }
     */
    public function customers()
    {
        $customers = Customer::query()
            ->orderBy('customer_reference')
            ->get()
            ->map(function (Customer $customer) {
                return [
                    'code' => $customer->customer_reference,
                    'name' => $customer->name,
                    'phoneNo' => $customer->mobile_number ?? '',
                    'email' => $customer->email ?? '',
                    'address' => $customer->address ?? '',
                ];
            })
            ->values();

        return response()->json($customers);
    }

    /**
     * Bank account master data for SAP.
     *
     * Returns the list of bank accounts in the format agreed with SAP.
     *
     * @group SAP Integration
     *
     * @response 200 {
     *   "code": "BANK001",
     *   "name": "KCB - KES",
     *   "currency": "KES",
     *   "accountNo": "123456789",
     *   "branch": "Kenyatta Avenue"
     * }
     */
    public function bankAccounts()
    {
        $accounts = [
            [
                'code' => 'BANK001',
                'name' => 'KCB - KES',
                'currency' => 'KES',
                'accountNo' => '123456789',
                'branch' => 'Kenyatta Avenue',
            ],
            [
                'code' => 'BANK002',
                'name' => 'ABSA - USD',
                'currency' => 'USD',
                'accountNo' => '7783746736473',
                'branch' => 'Kisumu',
            ],
            [
                'code' => 'BANK003',
                'name' => 'Equity - KES',
                'currency' => 'KES',
                'accountNo' => '7758747384',
                'branch' => 'Kisumu',
            ],
        ];

        return response()->json($accounts);
    }
}

