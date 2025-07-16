<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;

class LoginAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ip = Request::ip();
        $device = request()->header('User-Agent');
        $time = Carbon::now()->toDayDateTimeString();

        return (new MailMessage)
            ->subject('Login Alert - New Sign In Detected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We detected a login to your account.')
            ->line('**Date/Time**: ' . $time)
            ->line('**IP Address**: ' . $ip)
            ->line('**Device**: ' . $device)
            ->line('If this was you, no action is needed.')
            ->line('If you did **not** login, please reset your password immediately to secure your account.')
            ->action('Reset Password', url('/forgot-password'))
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
