<?php

namespace App\Payments;

use App\Payments\YoAPI;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

class YoPayments {

    private $username;
    private $password;
    private $callBack;
    private $yo;

    public function __construct()
    {
        $this->username = env("API_USERNAME");
        $this->password = env("API_PASSWORD");
        $this->callBack = 'https://webhook.site/759c7b75-86e2-41c8-83f7-478c2329c02f';
        $this->yo = new YoAPI($this->username, $this->password);
        $this->yo->set_instant_notification_url($this->callBack);
    }

    private function handleError($e)
    {
        if ($e->hasResponse()) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
        return ['response' => 'ERROR', 'error' => ['message' => $e->getMessage()]];
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
     *
     * @throws RequestException If the API request fails
     * @return mixed The response from the Ssentezo API
     */
    public function deposit($externalReference, $msisdn, $amount)
    {
        try {
            // ac_deposit_funds
            $res = $this->yo->ac_deposit_funds($msisdn, $amount, $externalReference);
            Log::info($res);
            return $res;
        } catch (RequestException $e) {
            Log::info($e);
            return $this->handleError($e);
        }
    }
}
