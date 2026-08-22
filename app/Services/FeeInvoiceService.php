<?php

namespace App\Services;

use App\Mail\FeeReceiptMail;
use App\Models\ClinicPatientDocument;
use App\Models\Fee;
use App\Models\StudentDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FeeInvoiceService
{
    public function generate(Fee $fee, bool $regenerate = false): StudentDocument|ClinicPatientDocument|null
    {
        return $fee->isClinicFee()
            ? $this->generateClinic($fee, $regenerate)
            : $this->generateSchool($fee, $regenerate);
    }

    protected function generateSchool(Fee $fee, bool $regenerate = false): ?StudentDocument
    {
        $fee->loadMissing(['student.business', 'student.parentGuardian', 'term', 'payments', 'invoiceDocument', 'business']);

        $student = $fee->student;
        $business = $student?->business ?? $fee->business;

        if (! $student || ! $business) {
            return null;
        }

        try {
            $binary = $this->renderPdf($fee, $business, $student->parentGuardian, $student->full_name, 'Student ID', $student->student_id);
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

    protected function generateClinic(Fee $fee, bool $regenerate = false): ?ClinicPatientDocument
    {
        $fee->loadMissing([
            'clinicPatient.business',
            'clinicPatient.parentGuardian',
            'term',
            'payments',
            'clinicInvoiceDocument',
            'business',
        ]);

        $patient = $fee->clinicPatient;
        $business = $patient?->business ?? $fee->business;

        if (! $patient || ! $business) {
            return null;
        }

        try {
            $binary = $this->renderPdf(
                $fee,
                $business,
                $patient->parentGuardian,
                $patient->full_name,
                'Patient no.',
                $patient->patient_number
            );
            $filename = 'invoice-'.($fee->receipt_number ?: $fee->uuid).'-'.Str::lower(Str::random(6)).'.pdf';
            $path = 'clinic-patient-documents/'.$business->id.'/'.$filename;

            Storage::disk('public')->put($path, $binary);

            $document = $fee->clinicInvoiceDocument;

            if ($document && $regenerate) {
                if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }
                $document->update([
                    'title' => 'Invoice '.($fee->receipt_number ?: '#'.$fee->id),
                    'description' => ucfirst((string) $fee->fee_type).' fee for '.$patient->full_name,
                    'file_path' => $path,
                    'mime_type' => 'application/pdf',
                    'size' => strlen($binary),
                    'meta' => [
                        'fee_id' => $fee->id,
                        'fee_uuid' => $fee->uuid,
                        'receipt_number' => $fee->receipt_number,
                    ],
                ]);
            } elseif (! $document) {
                $document = ClinicPatientDocument::create([
                    'business_id' => $business->id,
                    'clinic_patient_id' => $patient->id,
                    'uploaded_by' => null,
                    'type' => 'invoice',
                    'title' => 'Invoice '.($fee->receipt_number ?: '#'.$fee->id),
                    'description' => ucfirst((string) $fee->fee_type).' fee for '.$patient->full_name,
                    'file_path' => $path,
                    'mime_type' => 'application/pdf',
                    'size' => strlen($binary),
                    'meta' => [
                        'fee_id' => $fee->id,
                        'fee_uuid' => $fee->uuid,
                        'receipt_number' => $fee->receipt_number,
                    ],
                ]);
                $fee->update(['clinic_invoice_document_id' => $document->id]);
            }

            if ($regenerate && (float) $fee->amount_paid > 0) {
                $this->emailReceipt($fee, $binary, $filename);
            }

            return $document->fresh();
        } catch (\Throwable $e) {
            Log::error('Clinic fee invoice generation failed', [
                'fee_id' => $fee->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function renderPdf(Fee $fee, $business, $parent, string $childName, string $idLabel, ?string $idValue): string
    {
        return Pdf::loadView('fees.invoice-pdf', [
            'fee' => $fee,
            'business' => $business,
            'termLabel' => $fee->displayTerm(),
            'parent' => $parent,
            'childName' => $childName,
            'idLabel' => $idLabel,
            'idValue' => $idValue,
            'currency' => $business->displayCurrency(),
        ])->setPaper('a4')->output();
    }

    protected function emailReceipt(Fee $fee, string $binary, string $filename): void
    {
        $email = $fee->parentGuardian()?->email;

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new FeeReceiptMail($fee, $binary, $filename));
        } catch (\Throwable $e) {
            Log::warning('Fee receipt email failed', [
                'fee_id' => $fee->id,
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
