<?php

namespace App\Http\Controllers;

use App\Mail\BusinessAccountStatementMail;
use App\Models\Business;
use App\Services\BusinessAccountStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class BusinessAccountStatementController extends Controller
{
    public function __construct(
        protected BusinessAccountStatementService $statementService
    ) {}

    public function index(Request $request)
    {
        $business = $this->authorizedBusiness();
        [$from, $to] = $this->resolvePeriod($request);
        $statement = $this->statementService->build($business, $from, $to);

        return view('business-wallet.statement', [
            'business' => $business,
            'statement' => $statement,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function download(Request $request)
    {
        $business = $this->authorizedBusiness();
        [$from, $to] = $this->resolvePeriod($request);
        $statement = $this->statementService->build($business, $from, $to);

        $pdf = Pdf::loadView('business-wallet.statement-pdf', compact('statement'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($this->statementService->pdfFilename($statement));
    }

    public function email(Request $request)
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        [$from, $to] = $this->resolvePeriod($request);
        $statement = $this->statementService->build($business, $from, $to);

        $pdf = Pdf::loadView('business-wallet.statement-pdf', compact('statement'))
            ->setPaper('a4', 'portrait')
            ->output();

        Mail::to($validated['email'])->send(
            new BusinessAccountStatementMail(
                $business,
                $statement,
                $pdf,
                $this->statementService->pdfFilename($statement),
                $validated['message'] ?? null
            )
        );

        return redirect()
            ->route('business.statement.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()])
            ->with('success', 'Account statement sent to '.$validated['email'].' successfully.');
    }

    protected function resolvePeriod(Request $request): array
    {
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->copy()->subDays(30)->startOfDay();

        if ($from->gt($to)) {
            $from = $to->copy()->subDays(30)->startOfDay();
        }

        return [$from, $to];
    }

    protected function authorizedBusiness(): Business
    {
        $user = Auth::user();

        if (! $user || ! $user->business_id || (int) $user->business_id === 1) {
            abort(403, 'Account statements are only available for registered businesses.');
        }

        return Business::findOrFail($user->business_id);
    }
}
