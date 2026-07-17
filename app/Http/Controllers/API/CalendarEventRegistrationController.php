<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\CalendarEventRegistration;
use App\Models\ParentGuardian;
use App\Services\MarzPayCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CalendarEventRegistrationController extends Controller
{
    /**
     * Register a child for a school calendar event (authenticated parents).
     */
    public function store(Request $request, $eventId)
    {
        try {
            $event = CalendarEvent::query()
                ->where(function ($query) use ($eventId) {
                    $query->where('id', $eventId)->orWhere('uuid', $eventId);
                })
                ->where('status', 'published')
                ->first();

            if (! $event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found.',
                ], 404);
            }

            if (! $event->accepts_registrations) {
                return response()->json([
                    'success' => false,
                    'message' => 'This event is not open for registration.',
                ], 400);
            }

            if ($event->is_full) {
                return response()->json([
                    'success' => false,
                    'message' => 'This event is fully booked.',
                ], 400);
            }

            $amount = (int) round((float) ($event->price ?? 0));

            $validator = Validator::make($request->all(), [
                'child_name' => 'required|string|max:255',
                'child_age' => 'required|integer|min:1|max:18',
                'parent_name' => 'required|string|max:255',
                'parent_email' => 'required|email|max:255',
                'parent_phone' => 'required|string|max:20',
                'emergency_contact' => 'nullable|string|max:255',
                'medical_conditions' => 'nullable|string',
                'dietary_restrictions' => 'nullable|string',
                'payment_method' => ($amount > 0 ? 'required' : 'nullable').'|in:cash,card,bank_transfer,airtel_money,mtn_mobile_money,other',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();
            $paymentMethod = $request->payment_method ?: 'cash';
            $paymentStatus = $amount > 0 ? 'pending' : 'paid';

            $registration = CalendarEventRegistration::create([
                'calendar_event_id' => $event->id,
                'user_id' => $user instanceof ParentGuardian ? null : $user?->id,
                'parent_guardian_id' => $user instanceof ParentGuardian ? $user->id : null,
                'child_name' => $request->child_name,
                'child_age' => $request->child_age,
                'parent_name' => $request->parent_name,
                'parent_email' => $request->parent_email,
                'parent_phone' => $request->parent_phone,
                'emergency_contact' => $request->emergency_contact,
                'medical_conditions' => $request->medical_conditions,
                'dietary_restrictions' => $request->dietary_restrictions,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'registration_status' => 'confirmed',
                'notes' => $request->notes,
            ]);

            $event->increment('current_participants');
            $registration->load('calendarEvent');

            $checkout = app(MarzPayCheckoutService::class);
            $paymentResult = null;

            try {
                $paymentResult = $checkout->maybeInitiate($registration, $paymentMethod);
            } catch (\Throwable $error) {
                Log::error('School event payment initiation failed', [
                    'registration_id' => $registration->id,
                    'message' => $error->getMessage(),
                ]);
                $paymentResult = [
                    'success' => false,
                    'message' => 'Unable to initiate payment right now.',
                ];
            }

            $paymentMeta = $checkout->registrationPaymentMeta(
                $paymentResult,
                $paymentMethod,
                'Child registered. Complete payment to confirm.',
                $amount > 0
                    ? 'Child registered successfully!'
                    : 'Child registered successfully. No payment required.'
            );

            return response()->json([
                'success' => true,
                'message' => $paymentMeta['message'],
                'payment_initiated' => $paymentMeta['payment_initiated'],
                'payment_error' => $paymentMeta['payment_error'],
                'data' => [
                    'registration' => [
                        'id' => $registration->id,
                        'uuid' => $registration->uuid,
                        'child_name' => $registration->child_name,
                        'registration_status' => $registration->registration_status,
                        'payment_status' => $registration->payment_status,
                        'payment_method' => $registration->payment_method,
                        'created_at' => $registration->created_at,
                    ],
                    'payment' => $paymentMeta['payment'],
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('CalendarEventRegistration error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while registering.',
            ], 500);
        }
    }
}
