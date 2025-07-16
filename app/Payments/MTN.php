<?php

namespace App\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;




class MTN
{

    protected static $provider = "MTN";
    protected static $airtelBaseUrl = "https://proxy.momoapi.mtn.com/collection/";

    protected static $mtnToken;
    protected static $mtnKey;

    protected static function loadConfig()
    {
        self::$mtnToken = config("services.MTN.MTN_TOKEN");
        self::$mtnKey = config("services.MTN.MTN_KEY");
    }

    protected static function mtnBaseUrl()
    {
        return self::$airtelBaseUrl;
    }

    public static function mtnAuth()
    {
        try {
            //code...
            self::loadConfig();
            $url = self::mtnBaseUrl() . "token/";

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
                'Authorization' => 'Basic ' . self::$mtnToken,
                'Ocp-Apim-Subscription-Key' => self::$mtnKey
            ];
            $body = json_encode([
                'grant_type' => 'client_credentials',
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
        } catch (\Throwable $th) {
            //throw $th;
            return $th->getMessage();
        }
        // return self::$mtnToken;
    }

    /**
     * Handles the request to pay using the given data, transaction reference, and environment.
     *
     * @param mixed $data The data for the request
     * @param string $transaction_reference The transaction reference
     * @param string $environment The environment for the request
     * @throws \Throwable description of exception
     * @return mixed
     */
    public static function requestToPay($data, $transaction_reference, $environment)
    {
        try {
            //code...
            $accessToken = self::mtnAuth();
            $client = new Client();
            $url = self::mtnBaseUrl() . "v1_0/requesttopay";
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
                // 'Authorization' => 'Basic ' . self::$mtnToken,
                'Ocp-Apim-Subscription-Key' => self::$mtnKey,
                'X-Target-Environment' => $environment,
                'X-Reference-Id' => $transaction_reference,
                'Authorization' => 'Bearer ' . $accessToken
            ];

            // Encode $data as JSON
            $jsonData = json_encode($data);

            return  Curl::Post($url, $headers, $jsonData);
        } catch (\Throwable $th) {
            //throw $th;
            return $th;
        }
    }

    public static function mtnTransactionEquiry(string $transactionReference)
    {
        try {
            self::loadConfig();
            $url = self::mtnBaseUrl() . "v1_0/requesttopay/" . $transactionReference;
            $accessToken = self::mtnAuth();
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
                // 'Authorization' => 'Basic ' . self::$mtnToken,
                'Ocp-Apim-Subscription-Key' => self::$mtnKey,
                'X-Target-Environment' => "mtnuganda",
                'Authorization' => 'Bearer ' . $accessToken
            ];
            $response = Curl::Get($url, $headers);
            return $response;
        } catch (\Throwable $th) {
            //throw $th;
            return $th->getMessage();
        }
    }
}

//00e2b9e5-405b-4b58-a428-dd5ce2e88f76
//api key =>26980724daa440f6b2a5eebf263dc8b0


//New credentails
//x-reference-id => 07cd1b91-02e8-4519-9880-667d465020a5
//apiKey => c65f131668414a8d8fcbf7058f78c450
//apiUser => 07cd1b91-02e8-4519-9880-667d465020a5