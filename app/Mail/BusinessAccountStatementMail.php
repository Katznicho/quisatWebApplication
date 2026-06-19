<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BusinessAccountStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public array $statement,
        public string $pdfBinary,
        public string $pdfFilename,
        public ?string $customMessage = null
    ) {}

    public function envelope(): Envelope
    {
        $from = $this->statement['from']->format('M j, Y');
        $to = $this->statement['to']->format('M j, Y');

        return new Envelope(
            subject: config('app.name').' Account Statement ('.$from.' – '.$to.')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.business.account-statement',
            with: [
                'business' => $this->business,
                'statement' => $this->statement,
                'customMessage' => $this->customMessage,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
