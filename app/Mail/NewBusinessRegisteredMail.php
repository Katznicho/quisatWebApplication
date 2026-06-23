<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewBusinessRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public User $adminUser,
    ) {}

    public function envelope(): Envelope
    {
        $cc = config('mail.business_registration_notify_cc', []);

        return new Envelope(
            subject: 'New business registered on '.config('app.name').': '.$this->business->name,
            cc: ! empty($cc) ? $cc : [],
        );
    }

    public function content(): Content
    {
        $this->business->loadMissing('businessCategory');

        return new Content(
            markdown: 'mail.business.new-registration',
            with: [
                'business' => $this->business,
                'adminUser' => $this->adminUser,
                'categoryName' => $this->business->businessCategory?->name,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
