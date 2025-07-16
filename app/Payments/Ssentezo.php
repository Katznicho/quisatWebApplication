<?php
namespace App\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Ssentezo
{
    private static $apiUser;
    private static $apiKey;
    private static $baseUrl;
    private static $client;

    private static function init()
    {
        self::$apiUser = env("API_USER");
        self::$apiKey = env("API_KEY");
        self::$baseUrl = 'https://wallet.ssentezo.com/api/';
        self::$client = new Client();
    }

    private static function getHeaders()
    {
        $encodedString = base64_encode(self::$apiUser . ':' . self::$apiKey);
        return [
            'Authorization' => 'Basic ' . $encodedString,
            'Content-Type' => 'application/json',
        ];
    }

    private static function handleResponse($response)
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    private static function handleError($e)
    {
        if ($e->hasResponse()) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
        return ['response' => 'ERROR', 'error' => ['message' => $e->getMessage()]];
    }

    public static function checkBalance($currency = 'UGX')
    {
        self::init();
        try {
            $response = self::$client->post(self::$baseUrl . 'acc_balance', [
                'headers' => self::getHeaders(),
                'json' => ['currency' => $currency],
            ]);
            return self::handleResponse($response);
        } catch (RequestException $e) {
            return self::handleError($e);
        }
    }

    public static function verifyMsisdn($msisdn)
    {
        self::init();
        try {
            $response = self::$client->post(self::$baseUrl . 'msisdn-verification', [
                'headers' => self::getHeaders(),
                'json' => ['msisdn' => $msisdn],
            ]);
            return self::handleResponse($response);
        } catch (RequestException $e) {
            return self::handleError($e);
        }
    }

    public static function withdraw($externalReference, $msisdn, $amount, $currency = 'UGX', $reason, $name = null, $successCallback = null, $failureCallback = null)
    {
        self::init();
        $data = [
            'externalReference' => $externalReference,
            'msisdn' => $msisdn,
            'amount' => $amount,
            'currency' => $currency,
            'reason' => $reason,
            'name' => $name,
            'success_callback' => $successCallback,
            'failure_callback' => $failureCallback,
        ];

        try {
            $response = self::$client->post(self::$baseUrl . 'withdraw', [
                'headers' => self::getHeaders(),
                'json' => $data,
            ]);
            return self::handleResponse($response);
        } catch (RequestException $e) {
            return self::handleError($e);
        }
    }

    /**
     * Initiates a deposit request to the Ssentezo API.
     *
     * @param string $externalReference Unique reference for the deposit request
     * @param string $msisdn Mobile number of the recipient
     * @param float $amount The amount to be deposited
     * @param string $currency The currency of the deposit (default: UGX)
     * @param string $reason The reason for the deposit
     * @param string|null $name The name of the recipient (optional)
     * @param callable|null $successCallback Callback function for a successful deposit (optional)
     * @param callable|null $failureCallback Callback function for a failed deposit (optional)
     *
     * @throws RequestException If the API request fails
     * @return mixed The response from the Ssentezo API
     */
    public static function deposit($externalReference, $msisdn, $amount, $currency = 'UGX', $reason="payment", $name = null, $successCallback = null, $failureCallback = null)
    {
        self::init();
        $data = [
            'externalReference' => $externalReference,
            'msisdn' => $msisdn,
            'amount' => $amount,
            'currency' => $currency,
            'reason' => $reason,
            'name' => $name,
            'success_callback' => $successCallback,
            'failure_callback' => $failureCallback,
        ];

        try {
            $response = self::$client->post(self::$baseUrl . 'deposit', [
                'headers' => self::getHeaders(),
                'json' => $data,
            ]);
            return self::handleResponse($response);
        } catch (RequestException $e) {
            return self::handleError($e);
        }
    }

    /**
     * Retrieves the status of a transaction based on the provided external reference.
     *
     * @param string $externalReference Unique reference for the transaction
     * @throws RequestException If the API request fails
     * @return mixed The response from the Ssentezo API
     */
    public static function getStatus($externalReference)
    {
        self::init();
        try {
            $response = self::$client->post(self::$baseUrl . 'get_status/' . $externalReference, [
                'headers' => self::getHeaders(),
            ]);
            return self::handleResponse($response);
        } catch (RequestException $e) {
            return self::handleError($e);
        }
    }
}
