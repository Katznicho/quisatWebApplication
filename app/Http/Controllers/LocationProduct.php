<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;
use stdClass;

class LocationProduct extends Controller
{
    /**
     * Show product categories.
     */
    public function index()
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

            $res = $service->GetItemCategories();
            $decodedData = json_decode($res->return_value ?? '[]', true) ?? [];

            return view('categories.index', compact('decodedData'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'An error occurred while fetching categories.');
        }
    }

    /**
     * Show products for a given category code.
     */
    public function getProductsByCategory(string $code)
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

            $params = new stdClass();
            $params->categoryCode = trim($code);

            $res = $service->GetItemsByCategory($params);

            $decodedData = json_decode($res->return_value ?? '[]', true) ?? [];

            return view('categories.products', compact('decodedData', 'code'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'An error occurred while fetching products.');
        }
    }
}

