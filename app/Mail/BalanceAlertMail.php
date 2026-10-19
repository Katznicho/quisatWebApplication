<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BalanceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $business;
    public $currentBalance;
    public $threshold;
    public $isTest;

    /**
     * Create a new message instance.
     */
    public function __construct(Business $business, float $currentBalance, float $threshold, bool $isTest = false)
    {
        $this->business = $business;
        $this->currentBalance = $currentBalance;
        $this->threshold = $threshold;
        $this->isTest = $isTest;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->isTest 
            ? 'TEST: Low Balance Alert - GreensWell'
            : 'Low Balance Alert - GreensWell';

        return $this->subject($subject)
                    ->view('emails.balance-alert')
                    ->with([
                        'business' => $this->business,
                        'currentBalance' => $this->currentBalance,
                        'threshold' => $this->threshold,
                        'isTest' => $this->isTest,
                    ]);
    }
}
