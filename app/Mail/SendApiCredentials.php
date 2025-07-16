<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SendApiCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public string $key;
    public string $secret;
    public string $encoded;

    /**
     * Create a new message instance.
     */
    public function __construct(string $key, string $secret, string $encoded)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->encoded = $encoded;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your MarzPay API Credentials',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.api.credentials',
            with: [
                'key' => $this->key,
                'secret' => $this->secret,
                'encoded' => $this->encoded,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
