<?php

namespace App\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Airtel
{
    protected static $provider = "Airtel";
    protected static $airtelBaseUrl = "https://openapi.airtel.africa/";

    protected static $clientID;
    protected static $clientSecret;
    protected static $grantType;

    protected static function loadConfig()
    {
        self::$clientID = config("services.airtel.client_id");
        self::$clientSecret = config("services.airtel.client_secret");
        self::$grantType = config("services.airtel.grant_type");
    }

    protected static function airtelBaseUrl()
    {
        return self::$airtelBaseUrl;
    }

    public static function airtelAuth()
    {
        try {
            self::loadConfig();
            $url = self::airtelBaseUrl() . "auth/oauth2/token";
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => '*/*'
            ];
            $body = json_encode([
                'client_id' => self::$clientID,
                'client_secret' => self::$clientSecret,
                'grant_type' => self::$grantType
            ]);
            $response = Curl::PostToken($url, $headers, $body);


            // Check if the response is successful and contains the access token
            if ($response['success']) {
                // Extract the access token from the response
                $accessToken = $response['message']->access_token;
                return $accessToken;
            } else {
                // If the response is not successful, return null or handle the error accordingly
                return null;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }



    // public static function transactionEquiry(string $transactionReference)
    // {

    //     try {
    //         self::loadConfig();
    //         $accessToken =  self::airtelAuth();
    //         $url = self::airtelBaseUrl() . "v1/payments/" . $transactionReference;
    //         $headers = [
    //             'Content-Type' => 'application/json',
    //             'Accept' => '*/*',
    //             'X-Country' => 'UG',
    //             'X-Currency' => 'UGX',
    //             'Authorization' => 'Bearer ' . $accessToken
    //         ];
    //         $response = Curl::Get($url, $headers);

    //         return $response;
    //     } catch (\Exception $e) {
    //         return $e->getMessage();
    //     }
    // }

    // public static function makePayment($data, string $country, string $currency)
    // {
    //     try {
    //         self::loadConfig();
    //         $accessToken = self::airtelAuth();
    //         $body = '{
    //             "reference": "Card Topup",
    //             "subscriber": {
    //                 "country": "UG",
    //                 "currency": "UGX",
    //                 "msisdn": "759983853"
    //             },
    //             "transaction": {
    //                 "amount": 500,
    //                 "country": "UG",
    //                 "currency": "UGX",
    //                 "id": "TRX0000043545124565"
    //             }
    //         }';

    //         $url = self::airtelBaseUrl() . "merchant/v1/payments";
    //         $headers = [
    //             'Content-Type' => 'application/json',
    //             'Accept' => '*/*',
    //             'Authorization' => 'Bearer ' . $accessToken,
    //             'x-country' => $country,
    //             'x-currency' => $currency
    //         ];

    //         $response = Curl::Post($url, $headers, $body);
    //         return $response;
    //     } catch (\Exception $e) {
    //         return $e->getMessage();
    //     }
    // }

    /**
     * Initiates a payment request to Airtel's payment gateway.
     *
     * @param mixed $data The payment data to be sent in the request body.
     * @param string $country The country code for the transaction.
     * @param string $currency The currency code for the transaction.
     * @return array|string The response from Airtel's API as an array, or an error message if the request fails.
     */

    public static function makePayment($data, string $country, string $currency)
    {
        try {
            $airtelResponse = null;
            self::loadConfig();
            $accessToken =  self::airtelAuth();
            $client = new Client();
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
                'X-Country' => $country,
                'X-Currency' => $currency,
                'Authorization' => 'Bearer ' . $accessToken,
                'x-signature' => config("services.airtel.x_signature"),
                'x-key' => config("services.airtel.x_key"),
            ];
             // Ensure the data is properly encoded as JSON
            $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES);
            $request = new Request('POST', self::$airtelBaseUrl . 'merchant/v2/payments/', $headers, $jsonData);
            $res = $client->sendAsync($request)->wait();
            $airtelResponse = json_decode($res->getBody(), true);
            return  $airtelResponse;
        } catch (\Exception $e) {
            Log::error('Error making payment:', ['error' => $e->getMessage()]);
            return $e->getMessage();
        }
    }

    public static function transactionEquiry(string $transactionReference)
    {
        try {
            $airtelResponse = null;
            self::loadConfig();
            $accessToken =  self::airtelAuth();
            $client = new Client();
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
                'X-Country' => 'UG',
                'X-Currency' => 'UGX',
                'Authorization' => 'Bearer ' . $accessToken
            ];
            $request =  new Request('GET', self::$airtelBaseUrl . '/standard/v2/payments/' . $transactionReference, $headers);
            $res = $client->sendAsync($request)->wait();
            $airtelResponse = json_decode($res->getBody(), true);
            return  $airtelResponse;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    // public static function transactionEquiry(string $transactionReference)
    // {
    //     try {
    //         //code...
    //         $airtelResponse = null;
    //         self::loadConfig();
    //         $accessToken =  self::airtelAuth();
    //         $curl = curl_init();
    //         $headers = [
    //             'Content-Type' => 'application/json',
    //             'Accept' => '*/*',
    //             'X-Country' => 'UG',
    //             'X-Currency' => 'UGX',
    //             'Authorization' => 'Bearer ' . $accessToken
    //         ];

    //         curl_setopt_array($curl, array(
    //             CURLOPT_URL => self::$airtelBaseUrl . '/standard/v1/payments/' . $transactionReference,
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => '',
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 0,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => 'GET',
    //             CURLOPT_HTTPHEADER => $headers
    //         ));

    //         $response = curl_exec($curl);

    //         curl_close($curl);
    //         return $response;
    //     } catch (\Throwable $th) {
    //         //throw $th;
    //         return $th->getMessage();
    //     }
    // }
}
