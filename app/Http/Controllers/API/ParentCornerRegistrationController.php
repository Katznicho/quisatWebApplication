<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentCorner;
use App\Models\ParentCornerRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ParentCornerRegistrationController extends Controller
{
    /**
     * Register a parent for a parent corner event (supports guest registration)
     */
    public function store(Request $request, $eventId)
    {
        try {
            Log::info('ParentCornerRegistration::store - Request received', [
                'event_id' => $eventId,
                'request_data' => $request->all(),
            ]);
            
            // Find the event
            $event = ParentCorner::where('id', $eventId)
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
                'parent_name' => 'required|string|max:255',
                'parent_email' => 'required|email|max:255',
                'parent_phone' => 'required|string|max:20',
                'parent_address' => 'nullable|string|max:255',
                'number_of_children' => 'nullable|integer|min:0|max:20',
                'interests' => 'nullable|string',
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
            $registration = ParentCornerRegistration::create([
                'parent_corner_id' => $event->id,
                'user_id' => auth('sanctum')->id(), // Will be null for guests
                'parent_name' => $request->parent_name,
                'parent_email' => $request->parent_email,
                'parent_phone' => $request->parent_phone,
                'parent_address' => $request->parent_address,
                'number_of_children' => $request->number_of_children,
                'interests' => $request->interests,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'registration_status' => 'confirmed',
                'payment_status' => $request->payment_method === 'cash' ? 'pending' : 'pending',
            ]);

            // Increment current participants
            $event->increment('current_participants');

            return response()->json([
                'success' => true,
                'message' => 'Parent registered successfully!',
                'data' => [
                    'registration' => [
                        'id' => $registration->id,
                        'uuid' => $registration->uuid,
                        'parent_name' => $registration->parent_name,
                        'registration_status' => $registration->registration_status,
                        'payment_status' => $registration->payment_status,
                        'created_at' => $registration->created_at,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('ParentCornerRegistration error: ' . $e->getMessage());
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
            $event = ParentCorner::where('id', $eventId)->first();

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

            $registrations = ParentCornerRegistration::where('parent_corner_id', $event->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($registration) {
                    return [
                        'id' => $registration->id,
                        'uuid' => $registration->uuid,
                        'parent_name' => $registration->parent_name,
                        'parent_email' => $registration->parent_email,
                        'parent_phone' => $registration->parent_phone,
                        'parent_address' => $registration->parent_address,
                        'number_of_children' => $registration->number_of_children,
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
            Log::error('ParentCornerRegistration index error: ' . $e->getMessage());
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

            $registrations = ParentCornerRegistration::where('user_id', $user->id)
                ->with('parentCorner:id,title,start_date,end_date,location,image_url')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($registration) {
                    return [
                        'id' => $registration->id,
                        'uuid' => $registration->uuid,
                        'parent_name' => $registration->parent_name,
                        'number_of_children' => $registration->number_of_children,
                        'registration_status' => $registration->registration_status,
                        'payment_status' => $registration->payment_status,
                        'event' => $registration->parentCorner ? [
                            'id' => $registration->parentCorner->id,
                            'title' => $registration->parentCorner->title,
                            'start_date' => $registration->parentCorner->start_date,
                            'end_date' => $registration->parentCorner->end_date,
                            'location' => $registration->parentCorner->location,
                            'image_url' => $registration->parentCorner->image_url,
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
