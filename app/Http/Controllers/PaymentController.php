<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['eventAttendee.programEvent', 'eventAttendee.user', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('payments.index', compact('payments'));
    }

    public function pending()
    {
        $attendees = EventAttendee::with(['programEvent', 'user', 'payments'])
            ->whereHas('payments', function($query) {
                $query->havingRaw('SUM(amount) < amount_due');
            })
            ->orWhereDoesntHave('payments')
            ->get()
            ->filter(function($attendee) {
                return $attendee->balance > 0;
            });

        return view('payments.pending', compact('attendees'));
    }

    public function reports()
    {
        $totalPayments = Payment::sum('amount');
        $totalAttendees = EventAttendee::count();
        $paidAttendees = EventAttendee::whereHas('payments', function($query) {
            $query->havingRaw('SUM(amount) >= amount_due');
        })->count();
        $pendingAttendees = $totalAttendees - $paidAttendees;

        $paymentsByMethod = Payment::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        $recentPayments = Payment::with(['eventAttendee.programEvent', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('payments.reports', compact('totalPayments', 'totalAttendees', 'paidAttendees', 'pendingAttendees', 'paymentsByMethod', 'recentPayments'));
    }
}
