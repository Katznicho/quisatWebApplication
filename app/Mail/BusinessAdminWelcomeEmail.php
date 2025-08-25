<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Business;

class BusinessAdminWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $business;

    public function __construct(User $user, Business $business)
    {
        $this->user = $user;
        $this->business = $business;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . ' - Admin Account Created!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.business.admin-welcome',
            with: [
                'user' => $this->user,
                'business' => $this->business,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
