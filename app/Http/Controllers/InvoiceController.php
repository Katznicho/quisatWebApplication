<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\CustomerSoapClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('invoices.index');
    }

    public function view(string $invoice)
    {
        $invoiceNo = trim($invoice);

        try {
            $soap = app(CustomerSoapClient::class);
            $pdf = CustomerSoapClient::pdfFromReturn($soap->call('ViewCustomerInvoice', [
                'invoiceNo' => $invoiceNo,
            ]));

            if ($pdf) {
                return $this->pdfResponse($pdf, $invoiceNo);
            }

            $record = Invoice::findByNumber($invoiceNo);

            if (! $record) {
                abort(404, 'Invoice '.$invoiceNo.' was not found.');
            }

            $generated = Pdf::loadView('invoices.pdf', [
                'invoice' => $record,
                'customerName' => session('fullName') ?: $record['customerNo'],
                'customerEmail' => session('email'),
                'logoPath' => $this->logoPath(),
            ])->setPaper('a4')->output();

            return $this->pdfResponse($generated, $invoiceNo);
        } catch (\Throwable $th) {
            Log::error('ViewCustomerInvoice failed', [
                'invoiceNo' => $invoiceNo,
                'error' => $th->getMessage(),
            ]);

            abort(500, 'Failed to open invoice: '.$th->getMessage());
        }
    }

    public function send(Request $request, string $invoice)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $soap = app(CustomerSoapClient::class);
            $result = $soap->call('SendCustomerInvoice', [
                'invoiceNo' => trim($invoice),
                'sendToEmail' => trim($validated['email']),
            ]);

            $success = $result !== false
                && $result !== null
                && strtolower((string) $result) !== 'false';

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => is_string($result) && $result !== '' ? $result : 'Failed to send invoice.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => is_string($result) && $result !== '' && strtolower($result) !== 'true'
                    ? $result
                    : 'Invoice sent to '.$validated['email'],
            ]);
        } catch (\Throwable $th) {
            Log::error('SendCustomerInvoice failed', [
                'invoiceNo' => $invoice,
                'error' => $th->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send invoice: '.$th->getMessage(),
            ], 500);
        }
    }

    protected function pdfResponse(string $pdf, string $invoiceNo)
    {
        $filename = 'invoice-'.$invoiceNo.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    protected function logoPath(): ?string
    {
        $path = public_path('images/stabex_logo_big.jpg');

        return is_file($path) ? $path : null;
    }
}
