<?php

namespace App\Services;

use App\Mail\FeeReceiptMail;
use App\Models\Fee;
use App\Models\StudentDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FeeInvoiceService
{
    public function generate(Fee $fee, bool $regenerate = false): ?StudentDocument
    {
        $fee->loadMissing(['student.business', 'student.parentGuardian', 'term', 'payments', 'invoiceDocument']);

        $student = $fee->student;
        $business = $student?->business ?? $fee->business;

        if (! $student || ! $business) {
            return null;
        }

        try {
            $pdf = Pdf::loadView('fees.invoice-pdf', [
                'fee' => $fee,
                'student' => $student,
                'business' => $business,
                'termLabel' => $fee->displayTerm(),
                'parent' => $student->parentGuardian,
            ])->setPaper('a4');

            $binary = $pdf->output();
            $filename = 'invoice-'.($fee->receipt_number ?: $fee->uuid).'-'.Str::lower(Str::random(6)).'.pdf';
            $path = 'student-documents/'.$business->id.'/'.$filename;

            Storage::disk('public')->put($path, $binary);

            $document = $fee->invoiceDocument;

            if ($document && $regenerate) {
                if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }
                $document->update([
                    'title' => 'Invoice '.($fee->receipt_number ?: '#'.$fee->id),
                    'description' => ucfirst((string) $fee->fee_type).' fee for '.$student->full_name,
                    'file_path' => $path,
                    'file_url' => asset('storage/'.$path),
                    'mime_type' => 'application/pdf',
                    'size' => strlen($binary),
                    'meta' => [
                        'fee_id' => $fee->id,
                        'fee_uuid' => $fee->uuid,
                        'receipt_number' => $fee->receipt_number,
                    ],
                ]);
            } elseif (! $document) {
                $document = StudentDocument::create([
                    'business_id' => $business->id,
                    'student_id' => $student->id,
                    'uploaded_by' => null,
                    'type' => 'invoice',
                    'title' => 'Invoice '.($fee->receipt_number ?: '#'.$fee->id),
                    'description' => ucfirst((string) $fee->fee_type).' fee for '.$student->full_name,
                    'file_path' => $path,
                    'file_url' => asset('storage/'.$path),
                    'mime_type' => 'application/pdf',
                    'size' => strlen($binary),
                    'meta' => [
                        'fee_id' => $fee->id,
                        'fee_uuid' => $fee->uuid,
                        'receipt_number' => $fee->receipt_number,
                    ],
                ]);
                $fee->update(['invoice_document_id' => $document->id]);
            }

            if ($regenerate && (float) $fee->amount_paid > 0) {
                $this->emailReceipt($fee, $binary, $filename);
            }

            return $document->fresh();
        } catch (\Throwable $e) {
            Log::error('School fee invoice generation failed', [
                'fee_id' => $fee->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function emailReceipt(Fee $fee, string $binary, string $filename): void
    {
        $email = $fee->student?->parentGuardian?->email;

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new FeeReceiptMail($fee, $binary, $filename));
        } catch (\Throwable $e) {
            Log::warning('School fee receipt email failed', [
                'fee_id' => $fee->id,
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
