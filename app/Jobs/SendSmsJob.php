<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Services\MarzSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $smsMessageId) {}

    public function handle(MarzSmsService $marzSms): void
    {
        $sms = SmsMessage::find($this->smsMessageId);

        if ($sms) {
            $marzSms->deliver($sms);
        }
    }
}
