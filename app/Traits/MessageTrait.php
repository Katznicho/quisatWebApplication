<?php

namespace App\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

trait MessageTrait
{
    //  $apiKey = config::get('services.africastalking.api_key');
    //send message
    // public function sendMessage(string $phoneNumber, string $message)
    // {

    //     $phoneNumber = $this->formatMobileInternational($phoneNumber);
    //     $curl = curl_init();

    //     curl_setopt_array($curl, array(
    //       CURLOPT_URL => 'https://sms.thinkxcloud.com/api/send-message',
    //       CURLOPT_RETURNTRANSFER => true,
    //       CURLOPT_ENCODING => '',
    //       CURLOPT_MAXREDIRS => 10,
    //       CURLOPT_TIMEOUT => 0,
    //       CURLOPT_FOLLOWLOCATION => true,
    //       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //       CURLOPT_CUSTOMREQUEST => 'POST',
    //       CURLOPT_POSTFIELDS => array('api_key' => '352e72f15e324dc8dd78002a7d290c86','number' => $phoneNumber,'message' => $message),
    //     ));

    //     $response = curl_exec($curl);
    //     // echo $response;


    //      return $response;
    // }

    // public function formatMobileInternational($mobile)
    // {
    //     $length = strlen($mobile);
    //     $m = '256';
    //     //format 1: +256752665888
    //     if ($length == 13)
    //         return $mobile;
    //     elseif ($length == 12) //format 2: 256752665888
    //         return "+" . $mobile;
    //     elseif ($length == 10) //format 3: 0752665888
    //         return $m .= substr($mobile, 1);
    //     elseif ($length == 9) //format 4: 752665888
    //         return $m .= $mobile;

    //     return $mobile;
    // }

    public function sendMessage(string $phoneNumber, string $message)
    {
        $phoneNumber = $this->formatMobileInternational($phoneNumber);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.africastalking.com/version1/messaging',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'username=vugaug&to=' . urlencode($phoneNumber) . '&message=' . urlencode($message) . '&from=ATFintech',
            //CURLOPT_POSTFIELDS => 'username=vugaug&to=%2B256759983853&message=Hello%20World!&from=ATFintech',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'apiKey: atsk_2b14613c35d62eb1491013bcb3040eaa09340adfed66fee0c1e9b0934f85c5561db51e84'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }


    public function formatMobileInternational($mobile)
    {
        $length = strlen($mobile);
        $m = '+256';
        //format 1: +256752665888
        if ($length == 13)
            return $mobile;
        elseif ($length == 12) //format 2: 256752665888
            return "+" . $mobile;
        elseif ($length == 10) //format 3: 0752665888
            return $m .= substr($mobile, 1);
        elseif ($length == 9) //format 4: 752665888
            return $m .= $mobile;

        return $mobile;
    }
}
