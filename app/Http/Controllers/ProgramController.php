<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramEvent;
use App\Models\EventAttendee;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('programs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Program $program)
    {
        $program->load(['events.attendees', 'events.currency']);
        
        return view('programs.show', compact('program'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Program $program)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Program $program)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Program $program)
    {
        //
    }

    public function storeEvent(Request $request, Program $program)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'price' => 'required|numeric|min:0',
            'location' => 'required|string|max:255',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        $event = ProgramEvent::create([
            'program_ids' => [$program->id],
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'price' => $request->price,
            'status' => 'upcoming',
            'location' => $request->location,
            'currency_id' => $request->currency_id,
            'business_id' => Auth::user()->business_id,
            'user_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Event created successfully!');
    }

    public function storeAttendee(Request $request, $event)
    {
        $request->validate([
            'child_name' => 'required|string|max:255',
            'child_age' => 'required|integer|min:1|max:18',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:20',
            'parent_email' => 'nullable|email|max:255',
            'gender' => 'required|in:male,female',
            'payment_method' => 'required|in:cash,card,bank_transfer,airtel_money,mtn_mobile_money,other',
        ]);

        try {
            $programEvent = ProgramEvent::where('uuid', $event)->firstOrFail();
            
            $attendee = EventAttendee::create([
                'program_event_id' => $programEvent->id,
                'user_id' => Auth::id(),
                'child_name' => $request->child_name,
                'child_age' => $request->child_age,
                'parent_name' => $request->parent_name,
                'parent_phone' => $request->parent_phone,
                'parent_email' => $request->parent_email,
                'gender' => $request->gender,
                'payment_method' => $request->payment_method,
                'amount_paid' => 0,
                'amount_due' => $programEvent->price,
                'status' => 'pending',
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Child registered successfully!']);
            }

            return redirect()->back()->with('success', 'Child registered successfully!');
        } catch (\Exception $e) {
            Log::error('Error registering attendee: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to register child: ' . $e->getMessage()]);
            }

            return redirect()->back()->with('error', 'Failed to register child. Please try again.');
        }
    }

    public function storePayment(Request $request, $attendee)
    {
        Log::info('storePayment method called', [
            'attendee_uuid' => $attendee,
            'request_data' => $request->all(),
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::id()
        ]);
        
        // Find attendee by UUID manually since route model binding might not work
        $attendeeModel = EventAttendee::where('uuid', $attendee)->firstOrFail();
        
        Log::info('Attendee found', [
            'attendee_id' => $attendeeModel->id,
            'attendee_uuid' => $attendeeModel->uuid,
            'attendee_name' => $attendeeModel->child_name
        ]);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,bank_transfer,airtel_money,mtn_mobile_money,other',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'payment_date' => 'required|date',
        ]);

        try {
            $payment = Payment::create([
                'event_attendee_id' => $attendeeModel->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'notes' => $request->notes,
                'payment_date' => $request->payment_date,
                'user_id' => Auth::id() ?? 1, // Fallback to user ID 1 if not authenticated
            ]);

            if ($request->expectsJson()) {
                // Transform the payment to include accessors
                $paymentData = [
                    'id' => $payment->id,
                    'uuid' => $payment->uuid,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_method_display' => $payment->payment_method_display,
                    'payment_reference' => $payment->payment_reference,
                    'notes' => $payment->notes,
                    'payment_date' => $payment->payment_date,
                    'formatted_payment_date' => $payment->formatted_payment_date,
                    'user' => $payment->user ? [
                        'id' => $payment->user->id,
                        'name' => $payment->user->name
                    ] : null
                ];
                
                return response()->json([
                    'success' => true, 
                    'message' => 'Payment recorded successfully!',
                    'payment' => $paymentData,
                    'new_balance' => $attendeeModel->fresh()->balance
                ]);
            }

            return redirect()->back()->with('success', 'Payment recorded successfully!');
        } catch (\Exception $e) {
            Log::error('Error recording payment: ' . $e->getMessage(), [
                'attendee_id' => $attendeeModel->id,
                'exception' => $e
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()]);
            }

            return redirect()->back()->with('error', 'Failed to record payment. Please try again.');
        }
    }

    public function getAttendeePayments($attendee)
    {
        // Find attendee by UUID manually since route model binding might not work
        $attendeeModel = EventAttendee::where('uuid', $attendee)->firstOrFail();
        
        $attendeeModel->load(['payments.user', 'programEvent']);
        
        // Debug: Log the payments and total
        Log::info('Payment data for attendee', [
            'attendee_id' => $attendeeModel->id,
            'attendee_name' => $attendeeModel->child_name,
            'amount_due' => $attendeeModel->amount_due,
            'payments_count' => $attendeeModel->payments->count(),
            'payments_sum' => $attendeeModel->payments->sum('amount'),
            'total_paid_accessor' => $attendeeModel->total_paid,
            'balance_accessor' => $attendeeModel->balance
        ]);
        
        // Transform payments to include accessors
        $payments = $attendeeModel->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_method_display' => $payment->payment_method_display,
                'payment_reference' => $payment->payment_reference,
                'notes' => $payment->notes,
                'payment_date' => $payment->payment_date,
                'formatted_payment_date' => $payment->formatted_payment_date,
                'user' => $payment->user ? [
                    'id' => $payment->user->id,
                    'name' => $payment->user->name
                ] : null
            ];
        });
        
        // Calculate total manually to ensure accuracy
        $manualTotal = $payments->sum('amount');
        
        return response()->json([
            'attendee' => $attendeeModel,
            'payments' => $payments,
            'total_paid' => $manualTotal,
            'balance' => $attendeeModel->amount_due - $manualTotal,
            'payment_status' => $manualTotal >= $attendeeModel->amount_due ? 'paid' : ($manualTotal > 0 ? 'partial' : 'unpaid')
        ]);
    }
}
