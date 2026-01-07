<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProgramEvent;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProgramRegistrationController extends Controller
{
    /**
     * Register a child for a program event
     */
    public function store(Request $request, $eventId)
    {
        try {
            Log::info('🔵 [API] Program Registration - Request received', [
                'event_id' => $eventId,
                'request_data' => $request->all(),
            ]);

            $request->validate([
                'child_name' => 'required|string|max:255',
                'child_age' => 'required|integer|min:1|max:18',
                'parent_name' => 'required|string|max:255',
                'parent_phone' => 'required|string|max:20',
                'parent_email' => 'nullable|email|max:255',
                'gender' => 'required|in:male,female',
                'payment_method' => 'required|in:cash,card,bank_transfer,airtel_money,mtn_mobile_money,other',
            ]);

            Log::info('🔵 [API] Program Registration - Validation passed');

            // Get the authenticated user (parent or user)
            $user = Auth::guard('sanctum')->user();
            
            if (!$user) {
                Log::error('❌ [API] Program Registration - Authentication failed');
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            Log::info('🔵 [API] Program Registration - User authenticated', [
                'user_id' => $user->id ?? null,
                'user_type' => get_class($user),
            ]);

            // Find event by UUID first (matching web app pattern), then fallback to ID
            // This matches the web app's ProgramController::storeAttendee pattern
            $programEvent = ProgramEvent::where('uuid', $eventId)
                ->orWhere('id', $eventId)
                ->first();

            if (!$programEvent) {
                Log::error('❌ [API] Program Registration - Event not found', [
                    'event_id' => $eventId,
                    'searched_by' => ['id', 'uuid'],
                ]);
                
                // Log all events to help debug
                $allEvents = ProgramEvent::select('id', 'uuid', 'name', 'status')->limit(10)->get();
                Log::info('🔵 [API] Sample events in database:', $allEvents->toArray());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found. Event ID: ' . $eventId,
                ], 404);
            }

            Log::info('🔵 [API] Program Registration - Event found', [
                'event_id' => $programEvent->id,
                'event_uuid' => $programEvent->uuid,
                'event_name' => $programEvent->name,
                'event_status' => $programEvent->status,
            ]);

            // Check status - allow registration for most statuses, only block explicitly closed/cancelled
            $blockedStatuses = ['closed', 'cancelled', 'completed'];
            if (in_array(strtolower($programEvent->status ?? ''), $blockedStatuses)) {
                Log::warning('⚠️ [API] Program Registration - Event not available for registration', [
                    'event_id' => $programEvent->id,
                    'status' => $programEvent->status,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Event is not available for registration. Status: ' . $programEvent->status,
                ], 403);
            }

            // Get or create user record for parent
            $userId = null;
            if ($user instanceof \App\Models\ParentGuardian) {
                // Find or create a User record for the parent
                $parentUser = User::where('email', $user->email)
                    ->where('business_id', $user->business_id)
                    ->first();
                
                if (!$parentUser) {
                    $parentUser = User::create([
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'business_id' => $user->business_id,
                        'status' => 'active',
                        'branch_id' => null,
                        'password' => '',
                    ]);
                }
                $userId = $parentUser->id;
            } elseif ($user instanceof \App\Models\User) {
                $userId = $user->id;
            }

            // Check if already registered
            $existingAttendee = EventAttendee::where('program_event_id', $programEvent->id)
                ->where('user_id', $userId)
                ->where('child_name', $request->child_name)
                ->first();

            if ($existingAttendee) {
                return response()->json([
                    'success' => false,
                    'message' => 'This child is already registered for this event.',
                ], 422);
            }

            // Create attendee record
            $attendee = EventAttendee::create([
                'uuid' => (string) Str::uuid(),
                'program_event_id' => $programEvent->id,
                'user_id' => $userId,
                'child_name' => $request->child_name,
                'child_age' => $request->child_age,
                'parent_name' => $request->parent_name,
                'parent_phone' => $request->parent_phone,
                'parent_email' => $request->parent_email ?? ($user instanceof \App\Models\ParentGuardian ? $user->email : $user->email),
                'gender' => $request->gender,
                'payment_method' => $request->payment_method,
                'amount_paid' => 0,
                'amount_due' => $programEvent->price,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Child registered successfully!',
                'data' => [
                    'attendee' => [
                        'id' => $attendee->id,
                        'uuid' => $attendee->uuid,
                        'child_name' => $attendee->child_name,
                        'status' => $attendee->status,
                    ],
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error registering child for program event: ' . $e->getMessage(), [
                'event_id' => $eventId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register child. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get registration status for a user's children
     */
    public function index(Request $request, $eventId)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            // Get user ID
            $userId = null;
            if ($user instanceof \App\Models\ParentGuardian) {
                $parentUser = User::where('email', $user->email)
                    ->where('business_id', $user->business_id)
                    ->first();
                $userId = $parentUser?->id;
            } elseif ($user instanceof \App\Models\User) {
                $userId = $user->id;
            }

            if (!$userId) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'registrations' => [],
                    ],
                ]);
            }

            // Get program event
            $programEvent = ProgramEvent::where(function($q) use ($eventId) {
                    $q->where('id', $eventId)
                      ->orWhere('uuid', $eventId);
                })
                ->first();

            if (!$programEvent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found.',
                ], 404);
            }

            // Get registrations
            $registrations = EventAttendee::where('program_event_id', $programEvent->id)
                ->where('user_id', $userId)
                ->get()
                ->map(function ($attendee) {
                    return [
                        'id' => $attendee->id,
                        'uuid' => $attendee->uuid,
                        'child_name' => $attendee->child_name,
                        'child_age' => $attendee->child_age,
                        'gender' => $attendee->gender,
                        'status' => $attendee->status,
                        'amount_due' => (float) $attendee->amount_due,
                        'amount_paid' => (float) $attendee->amount_paid,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'registrations' => $registrations,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting registrations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get registrations.',
            ], 500);
        }
    }
}
