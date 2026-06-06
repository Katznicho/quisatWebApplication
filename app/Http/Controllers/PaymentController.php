<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesBusinessResource;
use App\Models\EventAttendee;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

class PaymentController extends Controller
{
    use AuthorizesBusinessResource;

    protected function eventAttendeeQuery(): Builder
    {
        $query = EventAttendee::query();

        if (! $this->isSuperAdmin()) {
            $query->whereHas('programEvent', function (Builder $eventQuery) {
                $eventQuery->where('business_id', $this->currentBusinessId());
            });
        }

        return $query;
    }

    protected function paymentQuery(): Builder
    {
        $query = Payment::query();

        if (! $this->isSuperAdmin()) {
            $query->whereHas('eventAttendee.programEvent', function (Builder $eventQuery) {
                $eventQuery->where('business_id', $this->currentBusinessId());
            });
        }

        return $query;
    }

    public function index()
    {
        $payments = $this->paymentQuery()
            ->with(['eventAttendee.programEvent', 'eventAttendee.user', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('payments.index', compact('payments'));
    }

    public function pending()
    {
        $attendees = $this->eventAttendeeQuery()
            ->with(['programEvent', 'user', 'payments'])
            ->get()
            ->filter(fn (EventAttendee $attendee) => $attendee->balance > 0);

        return view('payments.pending', compact('attendees'));
    }

    public function reports()
    {
        $paymentQuery = $this->paymentQuery();
        $attendeeQuery = $this->eventAttendeeQuery();

        $totalPayments = (clone $paymentQuery)->sum('amount');
        $totalAttendees = (clone $attendeeQuery)->count();
        $paidAttendees = (clone $attendeeQuery)
            ->whereHas('payments', function ($query) {
                $query->havingRaw('SUM(amount) >= amount_due');
            })
            ->count();
        $pendingAttendees = $totalAttendees - $paidAttendees;

        $paymentsByMethod = (clone $paymentQuery)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        $recentPayments = (clone $paymentQuery)
            ->with(['eventAttendee.programEvent', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('payments.reports', compact(
            'totalPayments',
            'totalAttendees',
            'paidAttendees',
            'pendingAttendees',
            'paymentsByMethod',
            'recentPayments',
        ));
    }
}
