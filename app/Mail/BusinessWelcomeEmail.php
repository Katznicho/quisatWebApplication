<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Business;

class BusinessWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $business;

    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . ' - Business Registration Successful!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.business.welcome',
            with: [
                'business' => $this->business,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
