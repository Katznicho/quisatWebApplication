<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KidsEvent;
use App\Models\KidsEventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class KidsEventRegistrationController extends Controller
{
    /**
     * Register a child for a kids event (supports guest registration)
     */
    public function store(Request $request, $eventId)
    {
        try {
            // Find the event
            $event = KidsEvent::where('id', $eventId)
                ->orWhere('uuid', $eventId)
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found.',
                ], 404);
            }

            // Check if event is full
            if ($event->is_full) {
                return response()->json([
                    'success' => false,
                    'message' => 'This event is fully booked.',
                ], 400);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'child_name' => 'required|string|max:255',
                'child_age' => 'required|integer|min:1|max:18',
                'parent_name' => 'required|string|max:255',
                'parent_email' => 'required|email|max:255',
                'parent_phone' => 'required|string|max:20',
                'emergency_contact' => 'nullable|string|max:255',
                'medical_conditions' => 'nullable|string',
                'dietary_restrictions' => 'nullable|string',
                'payment_method' => 'required|in:cash,card,bank_transfer,airtel_money,mtn_mobile_money,other',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Create registration
            $registration = KidsEventRegistration::create([
                'kids_event_id' => $event->id,
                'user_id' => auth('sanctum')->id(), // Will be null for guests
                'child_name' => $request->child_name,
                'child_age' => $request->child_age,
                'parent_name' => $request->parent_name,
                'parent_email' => $request->parent_email,
                'parent_phone' => $request->parent_phone,
                'emergency_contact' => $request->emergency_contact,
                'medical_conditions' => $request->medical_conditions,
                'dietary_restrictions' => $request->dietary_restrictions,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'registration_status' => 'confirmed',
                'payment_status' => $request->payment_method === 'cash' ? 'pending' : 'pending',
            ]);

            // Increment current participants
            $event->increment('current_participants');

            return response()->json([
                'success' => true,
                'message' => 'Child registered successfully!',
                'data' => [
                    'registration' => [
                        'id' => $registration->id,
                        'uuid' => $registration->uuid,
                        'child_name' => $registration->child_name,
                        'registration_status' => $registration->registration_status,
                        'payment_status' => $registration->payment_status,
                        'created_at' => $registration->created_at,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('KidsEventRegistration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while registering.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get registrations for a specific event (authenticated users only)
     */
    public function index(Request $request, $eventId)
    {
        try {
            $event = KidsEvent::where('id', $eventId)
                ->orWhere('uuid', $eventId)
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found.',
                ], 404);
            }

            // Only allow business owners/staff to view registrations
            $user = auth('sanctum')->user();
            if (!$user || ($event->business_id && $user->business_id !== $event->business_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }

            $registrations = KidsEventRegistration::where('kids_event_id', $event->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($registration) {
                    return [
                        'id' => $registration->id,
                        'uuid' => $registration->uuid,
                        'child_name' => $registration->child_name,
                        'child_age' => $registration->child_age,
                        'parent_name' => $registration->parent_name,
                        'parent_email' => $registration->parent_email,
                        'parent_phone' => $registration->parent_phone,
                        'registration_status' => $registration->registration_status,
                        'payment_status' => $registration->payment_status,
                        'payment_method' => $registration->payment_method,
                        'created_at' => $registration->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'registrations' => $registrations,
                    'total' => $registrations->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('KidsEventRegistration index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching registrations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's own registrations
     */
    public function myRegistrations(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 401);
            }

            $registrations = KidsEventRegistration::where('user_id', $user->id)
                ->with('kidsEvent:id,title,start_date,end_date,location,image_url')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($registration) {
                    return [
                        'id' => $registration->id,
                        'uuid' => $registration->uuid,
                        'child_name' => $registration->child_name,
                        'child_age' => $registration->child_age,
                        'registration_status' => $registration->registration_status,
                        'payment_status' => $registration->payment_status,
                        'event' => $registration->kidsEvent ? [
                            'id' => $registration->kidsEvent->id,
                            'title' => $registration->kidsEvent->title,
                            'start_date' => $registration->kidsEvent->start_date,
                            'end_date' => $registration->kidsEvent->end_date,
                            'location' => $registration->kidsEvent->location,
                            'image_url' => $registration->kidsEvent->image_url,
                        ] : null,
                        'created_at' => $registration->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'registrations' => $registrations,
                    'total' => $registrations->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('My registrations error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching your registrations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

