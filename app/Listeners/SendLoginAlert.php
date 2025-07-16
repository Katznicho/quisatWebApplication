<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Notifications\LoginAlertNotification;

class SendLoginAlert
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // Send login alert notification to the user
        $event->user->notify(new LoginAlertNotification);
    }
}
