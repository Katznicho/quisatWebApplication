<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChurchVisitor;
use App\Models\ChurchVisitorRegistration;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Services\ChurchVisitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ChurchVisitorController extends Controller
{
    public function __construct(protected ChurchVisitorService $visitors) {}

    public function index(Request $request)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');
        $date = (string) $request->query('date', Carbon::now($business->timezoneName())->toDateString());
        $includeNotes = $user instanceof User;

        $query = ChurchVisitorRegistration::query()
            ->with(['visitor.family', 'visitor.registeredBy', 'attendance.checkedInBy', 'attendance.checkedOutBy', 'classRoom'])
            ->where('business_id', $business->id)
            ->whereDate('service_date', $date)
            ->where('status', '!=', 'cancelled')
            ->whereHas('visitor', fn ($visitor) => $visitor->whereNull('removed_at'));

        if ($user instanceof ParentGuardian) {
            $query->whereHas('visitor', fn ($visitor) => $visitor->where('family_id', $user->id)->whereNull('removed_at'));
            $includeNotes = true;
        } elseif (! $user instanceof User) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Visiting children loaded.',
            'data' => [
                'family_name' => $user instanceof ParentGuardian ? $user->full_name : null,
                'visitors' => $query->orderBy('id')->get()->map(function ($row) use ($includeNotes, $user) {
                    $payload = $this->visitors->payload($row, $includeNotes);
                    if ($user instanceof User) {
                        unset($payload['pickup_code']);
                    }

                    return $payload;
                })->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof ParentGuardian) {
            return response()->json(['success' => false, 'message' => 'Only a parent can add a visiting child.'], 403);
        }

        $business = $request->get('business');
        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'date_of_birth' => 'nullable|date|before:today',
            'age' => 'nullable|integer|min:0|max:18',
            'relationship' => 'required|string|max:80',
            'care_notes' => 'nullable|string|max:1000',
            'church_service_id' => 'required|integer',
            'service_date' => 'required|date',
            'client_token' => 'nullable|string|max:64',
        ]);

        if (empty($data['date_of_birth']) && ! isset($data['age'])) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'Enter a date of birth or an age.',
            ]);
        }

        $registration = $this->visitors->register($user, (int) $business->id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Visiting child saved. They are awaiting church check-in.',
            'data' => ['visitor' => $this->visitors->payload($registration, true)],
        ], 201);
    }

    public function cancel(Request $request, ChurchVisitorRegistration $registration)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof ParentGuardian) {
            abort(403);
        }
        $this->assertBusiness($request, $registration);
        $registration = $this->visitors->cancel($user, $registration);

        return response()->json([
            'success' => true,
            'message' => 'Registration cancelled. This was not recorded as a check-out.',
            'data' => ['visitor' => $this->visitors->payload($registration, true)],
        ]);
    }

    public function destroy(Request $request, ChurchVisitor $churchVisitor)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof ParentGuardian) {
            abort(403);
        }
        if ((int) $churchVisitor->business_id !== (int) $request->get('business')->id) {
            abort(404);
        }
        $this->visitors->remove($user, $churchVisitor);

        return response()->json([
            'success' => true,
            'message' => 'Visiting child removed from your list. The church attendance history remains saved.',
        ]);
    }

    public function arrive(Request $request, ChurchVisitorRegistration $registration)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof User) {
            abort(403, 'Only staff can confirm arrival.');
        }
        $this->assertBusiness($request, $registration);
        $data = $request->validate(['class_room_id' => 'required|integer']);
        $registration = $this->visitors->arrive($user, $registration, (int) $data['class_room_id']);

        return response()->json([
            'success' => true,
            'message' => 'Arrival confirmed.',
            'data' => ['visitor' => $this->visitors->payload($registration, true)],
        ]);
    }

    public function checkout(Request $request, ChurchVisitorRegistration $registration)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof User) {
            abort(403, 'Only staff can check out a visiting child.');
        }
        $this->assertBusiness($request, $registration);
        $data = $request->validate([
            'pickup_code' => 'required|string|max:8',
            'collector_name' => 'nullable|string|max:255',
        ]);
        $registration = $this->visitors->checkout($user, $registration, $data['pickup_code'], $data['collector_name'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Visiting child checked out.',
            'data' => ['visitor' => $this->visitors->payload($registration, true)],
        ]);
    }

    private function assertBusiness(Request $request, ChurchVisitorRegistration $registration): void
    {
        if ((int) $registration->business_id !== (int) $request->get('business')->id) {
            abort(404);
        }
    }
}
