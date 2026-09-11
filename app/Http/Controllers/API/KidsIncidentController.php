<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KidsIncident;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use App\Services\KidsChurchNotificationService;
use Illuminate\Http\Request;

class KidsIncidentController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = KidsIncident::query()
            ->with(['student:id,first_name,last_name'])
            ->where('business_id', $businessId)
            ->latest();

        if ($user instanceof ParentGuardian) {
            $childIds = Student::query()
                ->where('parent_guardian_id', $user->id)
                ->where('business_id', $businessId)
                ->pluck('id');
            $query->whereIn('student_id', $childIds);
        }

        $items = $query->paginate(min((int) $request->query('per_page', 30), 50));
        $items->getCollection()->transform(fn (KidsIncident $item) => $this->transform($item));

        return response()->json([
            'success' => true,
            'data' => ['incidents' => $items->items()],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'Only staff can log incidents.'], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:physical,behavioral,health,other',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:4000',
            'action_taken' => 'nullable|string|max:2000',
        ]);

        $student = Student::where('business_id', $request->get('business_id'))
            ->where('id', $validated['student_id'])
            ->first();

        if (! $student) {
            return response()->json(['success' => false, 'message' => 'Child not found.'], 404);
        }

        $incident = KidsIncident::create([
            ...$validated,
            'business_id' => $request->get('business_id'),
            'reported_by' => $user->id,
        ]);

        app(KidsChurchNotificationService::class)->notifyIncident($incident);

        return response()->json([
            'success' => true,
            'message' => 'Incident logged and parent notified.',
            'data' => ['incident' => $this->transform($incident->load('student:id,first_name,last_name'))],
        ], 201);
    }

    protected function transform(KidsIncident $incident): array
    {
        return [
            'uuid' => $incident->uuid,
            'type' => $incident->type,
            'title' => $incident->title,
            'description' => $incident->description,
            'action_taken' => $incident->action_taken,
            'student' => $incident->student ? [
                'id' => $incident->student->id,
                'full_name' => $incident->student->full_name,
            ] : null,
            'notified_parent_at' => optional($incident->notified_parent_at)?->toIso8601String(),
            'created_at' => optional($incident->created_at)?->toIso8601String(),
        ];
    }
}
