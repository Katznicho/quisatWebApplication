<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class GeneralController extends Controller
{
    public static function sendPatchRequest(string $url, array $data): array
    {
        try {
            $client = new Client();
            $headers = [
                'If-Match' => '*',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(config('app.soapUsername') . ':' . config('app.soapPassword')),
            ];

            $body = json_encode($data);
            $request = new Psr7Request('PATCH', $url, $headers, $body);
            $res = $client->sendAsync($request)->wait();

            return ['status' => 'success', 'response' => $res];
        } catch (Exception $ex) {
            return ['status' => 'error', 'message' => $ex->getMessage()];
        }
    }
}

