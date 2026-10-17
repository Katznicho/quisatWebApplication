<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalkinVisit;
use App\Services\TeacherClassScope;
use App\Services\WalkInVisitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class WalkInVisitorController extends Controller
{
    public function __construct(private WalkInVisitorService $visitors) {}

    public function index(Request $request)
    {
        $staff = $this->staff($request);
        $business = $request->get('business');
        $date = (string) $request->query('date', Carbon::now($business->timezoneName())->toDateString());

        $scope = app(TeacherClassScope::class);
        $base = WalkinVisit::query()
            ->where('business_id', $business->id)
            ->whereDate('service_date', $date)
            ->when(! $scope->seesAllClasses($staff), function ($query) use ($scope, $staff, $business) {
                $allowed = $scope->assignedClassIds($staff, (int) $business->id);
                $query->whereIn('class_room_id', $allowed === [] ? [0] : $allowed);
            })
            ->when($request->query('class_room_id'), fn ($query, $classId) => $query->where('class_room_id', $classId))
            ->when($request->query('church_service_id'), fn ($query, $serviceId) => $query->where('church_service_id', $serviceId))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('child', fn ($child) => $child->where('full_name', 'like', '%'.$search.'%'))
                        ->orWhereHas('family', function ($family) use ($search) {
                            $family->where('guardian_name', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%')
                                ->orWhere('phone_digits', 'like', '%'.preg_replace('/\D/', '', $search).'%');
                        });
                });
            });
        $inside = (clone $base)->where('status', 'checked_in')->count();
        $checkedOut = (clone $base)->where('status', 'checked_out')->count();

        $visits = (clone $base)
            ->with(['child.family', 'classRoom', 'checkedInBy', 'checkedOutBy'])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status === 'inside' ? 'checked_in' : $status))
            ->orderBy('checked_in_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Walk-in visitors loaded.',
            'data' => [
                'inside' => $inside,
                'checked_out' => $checkedOut,
                'can_approve_exception' => $staff->isAdmin() || $staff->isBusinessAdmin(),
                'visits' => $visits->map(fn (WalkinVisit $visit) => $this->visitors->listPayload($visit, false, true))->values(),
            ],
        ]);
    }

    public function lookup(Request $request)
    {
        $this->staff($request);
        $business = $request->get('business');
        $data = $request->validate(['phone' => 'required|string|max:40']);

        return response()->json([
            'success' => true,
            'message' => 'Matching families loaded. Confirm the family before using it.',
            'data' => [
                'families' => $this->visitors->lookup((int) $business->id, $data['phone']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $staff = $this->staff($request);
        $business = $request->get('business');
        $data = $request->validate([
            'client_token' => 'required|string|max:64',
            'family_id' => 'nullable|integer',
            'guardian_name' => 'required|string|max:120',
            'phone' => 'required|string|max:40',
            'relationship' => 'required|string|max:80',
            'collector_name' => 'required|string|max:120',
            'participation_consent' => 'required|boolean',
            'follow_up_consent' => 'nullable|boolean',
            'church_service_id' => 'required|integer',
            'service_date' => 'required|date',
            'children' => 'required|array|min:1',
            'children.*.id' => 'nullable|integer',
            'children.*.full_name' => 'required|string|max:120',
            'children.*.date_of_birth' => 'nullable|date|before:today',
            'children.*.age' => 'nullable|integer|min:0|max:18',
            'children.*.class_room_id' => 'required|integer',
            'children.*.care_notes' => 'nullable|string|max:1000',
            'children.*.confirm_new' => 'nullable|boolean',
        ]);

        foreach ($data['children'] as $index => $child) {
            if (empty($child['id']) && empty($child['date_of_birth']) && ! isset($child['age'])) {
                throw ValidationException::withMessages([
                    'children.'.$index.'.date_of_birth' => 'Enter a date of birth or an age.',
                ]);
            }
        }

        if (strlen(preg_replace('/\D/', '', $data['phone']) ?: '') < 7) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid phone number.']);
        }

        $checkin = $this->visitors->register($staff, (int) $business->id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Visitors checked in.',
            'data' => [
                'family' => [
                    'guardian_name' => $checkin->family?->guardian_name,
                    'collector_name' => $checkin->family?->collector_name,
                    'phone' => $checkin->family?->phone,
                ],
                'visits' => $checkin->visits->map(fn (WalkinVisit $visit) => $this->visitors->listPayload($visit, true, false))->values(),
            ],
        ], 201);
    }

    public function show(Request $request, WalkinVisit $visit)
    {
        $this->staff($request);
        $this->sameChurch($request, $visit);

        return response()->json([
            'success' => true,
            'message' => 'Visit loaded.',
            'data' => ['visit' => $this->visitors->listPayload($visit, true, true)],
        ]);
    }

    public function checkout(Request $request)
    {
        $staff = $this->staff($request);
        $business = $request->get('business');
        $data = $request->validate([
            'visit_ids' => 'required|array|min:1',
            'visit_ids.*' => 'integer',
            'pickup_code' => 'nullable|string|max:8',
            'collector_name' => 'nullable|string|max:120',
            'collector_confirmed' => 'required|boolean',
            'exception_reason' => 'nullable|string|max:255',
        ]);

        $visits = $this->visitors->checkout($staff, (int) $business->id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Selected children checked out.',
            'data' => [
                'visits' => collect($visits)->map(fn (WalkinVisit $visit) => $this->visitors->listPayload($visit, false, false))->values(),
            ],
        ]);
    }

    private function staff(Request $request): User
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof User) {
            abort(403, 'Only church staff can register walk-in visitors.');
        }

        return $user;
    }

    private function sameChurch(Request $request, WalkinVisit $visit): void
    {
        if ((int) $visit->business_id !== (int) $request->get('business')->id) {
            abort(404);
        }
    }
}
