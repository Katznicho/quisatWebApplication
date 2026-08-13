<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Support\TenantScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeReportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $type = $request->get('type', 'payments');
        $rows = $this->rows($type);
        $filename = 'fee-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Student', 'Parent', 'Term', 'Type', 'Amount', 'Paid', 'Balance', 'Status', 'Method', 'Receipt', 'Due date', 'Payment date', 'External system', 'External code']);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request)
    {
        $type = $request->get('type', 'payments');
        $fees = $this->fees($type);
        $stats = $this->stats($fees);
        $title = $this->title($type);

        $pdf = Pdf::loadView('fees.report-pdf', [
            'title' => $title,
            'fees' => $fees,
            'stats' => $stats,
            'methods' => $this->methodBreakdown($fees),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('fee-'.$type.'-'.now()->format('Ymd-His').'.pdf');
    }

    protected function fees(string $type)
    {
        $query = Fee::query()->with(['student.parentGuardian', 'term']);
        TenantScope::apply($query);

        return match ($type) {
            'pending' => $query->whereIn('payment_status', ['pending', 'partial', 'overdue'])->orderBy('due_date')->get(),
            'recent' => $query->whereNotNull('payment_date')->orderByDesc('payment_date')->limit(100)->get(),
            'methods' => $query->where('amount_paid', '>', 0)->orderByDesc('payment_date')->get(),
            default => $query->orderByDesc('created_at')->get(),
        };
    }

    protected function rows(string $type): array
    {
        return $this->fees($type)->map(function (Fee $fee) {
            return [
                $fee->student?->full_name,
                $fee->student?->parentGuardian?->full_name,
                $fee->displayTerm(),
                $fee->fee_type,
                $fee->amount,
                $fee->amount_paid,
                $fee->balance,
                $fee->payment_status,
                $fee->payment_method,
                $fee->receipt_number,
                optional($fee->due_date)->toDateString(),
                optional($fee->payment_date)->toDateString(),
                $fee->external_payment_system,
                $fee->external_student_code,
            ];
        })->all();
    }

    protected function stats($fees): array
    {
        return [
            'total_billed' => (float) $fees->sum('amount'),
            'total_paid' => (float) $fees->sum('amount_paid'),
            'total_pending' => (float) $fees->sum(fn (Fee $fee) => max($fee->remainingBalance(), 0)),
            'arrears' => (float) $fees->sum(fn (Fee $fee) => $fee->arrears()),
            'credits' => (float) $fees->sum(fn (Fee $fee) => $fee->credit()),
            'count' => $fees->count(),
        ];
    }

    protected function title(string $type): string
    {
        return match ($type) {
            'pending' => 'Pending school fees',
            'recent' => 'Recent fee payments',
            'methods' => 'Payments by method',
            default => 'All school fee payments',
        };
    }

    protected function methodBreakdown($fees): array
    {
        return [
            'MarzPay' => (float) $fees->filter(fn (Fee $fee) => in_array($fee->payment_method, ['mobile_money', 'card'], true))->sum('amount_paid'),
            'Cash' => (float) $fees->where('payment_method', 'cash')->sum('amount_paid'),
            'Other / attached proof' => (float) $fees->where('payment_method', 'other')->sum('amount_paid'),
        ];
    }
}
