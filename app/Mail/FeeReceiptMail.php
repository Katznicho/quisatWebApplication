<?php

namespace App\Mail;

use App\Models\Fee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeeReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Fee $fee,
        public string $pdfBinary,
        public string $pdfFilename,
    ) {}

    public function envelope(): Envelope
    {
        $receipt = $this->fee->receipt_number ?: '#'.$this->fee->id;

        return new Envelope(
            subject: 'School fee receipt '.$receipt,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.fees.receipt',
            with: [
                'fee' => $this->fee,
                'student' => $this->fee->student,
                'business' => $this->fee->business ?? $this->fee->student?->business,
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
