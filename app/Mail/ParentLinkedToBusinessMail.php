<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\ParentGuardian;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParentLinkedToBusinessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ParentGuardian $parent,
        public Business $business
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been linked to '.$this->business->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.parent-linked-to-business',
            with: [
                'parentName' => $this->parent->full_name ?: 'Parent',
                'businessName' => $this->business->name,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
