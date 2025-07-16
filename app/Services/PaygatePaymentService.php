<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaygatePaymentService
{
    protected $wallet;
    protected $callbackBase;

    public function __construct()
    {
        $this->wallet = config('paygate.wallet'); // from config file
        $this->callbackBase = config('paygate.callback_url'); // from config file
    }

    /**
     * Generate encrypted address from PayGate API
     */
    public function getEncryptedAddress($orderNumber)
    {
        $callback = urlencode("{$this->callbackBase}?order={$orderNumber}");
        $url = "https://api.paygate.to/control/wallet.php?address={$this->wallet}&callback={$callback}";

        $response = Http::get($url);



        if ($response->ok()) {
            // dd($response->json(['address_in']));
            return $response->json()['address_in'];
        }

        throw new \Exception("Unable to retrieve encrypted address.");
    }

    /**
     * Generate the payment redirect URL
     */
    // public function generatePaymentUrl($orderNumber, $amount, $email, $provider = 'moonpay', $currency = 'USD')
    // {
    //     $encryptedAddress = $this->getEncryptedAddress($orderNumber);

    //     $query = http_build_query([
    //         'address'  => $encryptedAddress,
    //         'amount'   => $amount,
    //         'provider' => $provider,
    //         'email'    => $email,
    //         'currency' => $currency,
    //     ]);

    //     return "https://checkout.paygate.to/process-payment.php?" . $query;
    // }

    public function generatePaymentUrl($orderNumber, $amount, $email, $provider = 'moonpay', $currency = 'USD')
{
    // $uniqueId = $uniqueId ?? uniqid();

    // $encryptedAddress = $this->getEncryptedWallet($uniqueId);
    $encryptedAddress = $this->getEncryptedAddress($orderNumber);

    if (!$encryptedAddress) {
        return null;
    }

    $query = 'address=' . $encryptedAddress
           . '&amount=' . $amount
           . '&provider=' . $provider
           . '&email=' . urlencode($email)
           . '&currency=' . $currency;

    return "https://checkout.paygate.to/process-payment.php?$query";
}

}
