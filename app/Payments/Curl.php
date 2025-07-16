<?php

namespace App\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Curl
{
    private static $client;
    private static $defaultHeaders = [];

    public static function initialize()
    {
        self::$client = new Client();
    }

    public static function Get($url, $headers = [])
    {
        if (!self::$client) {
            self::initialize();
        }

        $headers = array_merge(self::$defaultHeaders, $headers);

        try {
            $response = self::$client->request('GET', $url, [
                'headers' => $headers
            ]);

            return self::processResponse($response);
        } catch (GuzzleException $e) {
            return self::formatErrorResponse($e->getMessage());
        }
    }

    public static function Post($url, $headers, $body)
    {
        return self::request('POST', $url, $headers, $body);
    }

    public static function PostToken($url, $headers, $body)
    {
        return self::request('POST', $url, $headers, $body);
    }

    private static function request($method, $url, $headers, $body)
    {
        if (!self::$client) {
            self::initialize();
        }

        $headers = array_merge(self::$defaultHeaders, $headers);

        try {
            $response = self::$client->request($method, $url, [
                'headers' => $headers,
                'body' => $body
            ]);

            return self::processResponse($response);
        } catch (GuzzleException $e) {
            return self::formatErrorResponse($e->getMessage());
        }
    }

    private static function processResponse($response)
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => json_decode($body),
        ];
    }

    private static function formatErrorResponse($errorMessage)
    {
        error_log($errorMessage);
        return [
            'success' => false,
            'message' => $errorMessage,
            'client' => ''
        ];
    }
}
