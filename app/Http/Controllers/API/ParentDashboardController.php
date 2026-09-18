<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BroadcastAnnouncement;
use App\Models\CalendarEvent;
use App\Models\ClassAssignment;
use App\Models\Fee;
use App\Models\KidsLesson;
use App\Models\MemoryWallItem;
use App\Models\ParentGuardian;
use App\Models\PickupCode;
use App\Models\Student;
use App\Models\StudentAcademicEntry;
use App\Models\StudentCharacterReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ParentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if (!$user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents/guardians can access this resource.',
            ], 403);
        }

        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found. Please contact support.',
            ], 403);
        }

        $timezone = config('app.timezone', 'Africa/Nairobi');
        $today = Carbon::now($timezone);
        $churchIds = $user->scopedChurchBusinessIds((int) $business->id) ?: [(int) $business->id];
        $churchCheckInId = $user->preferredChurchBusinessId((int) $business->id);
        $usesChurchScope = $business->isChurch() && ! $business->isSchool();
        if ($usesChurchScope && $churchCheckInId) {
            $children = $user->studentsForChurchCheckIn($churchCheckInId);
        } else {
            $children = $user->students()
                ->whereIn('business_id', $usesChurchScope ? $churchIds : [(int) $business->id])
                ->with(['classRoom:id,name,code'])
                ->get();
        }
        $classRoomIds = $children->pluck('class_room_id')->filter()->unique()->values();
        $linkedOrganizations = $this->linkedOrganizations($user, $business);

        $announcements = BroadcastAnnouncement::query()
            ->where('business_id', $business->id)
            ->whereIn('status', ['published', 'sent'])
            ->where(function ($q) {
                $q->whereNull('target_roles')
                    ->orWhereJsonContains('target_roles', 'all_users')
                    ->orWhereJsonContains('target_roles', 'parents');
            })
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (BroadcastAnnouncement $announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'type' => $announcement->type,
                    'sent_at' => optional($announcement->sent_at)->toIso8601String(),
                ];
            })
            ->values();

        $events = CalendarEvent::query()
            ->whereIn('business_id', $usesChurchScope ? $churchIds : [(int) $business->id])
            ->where('status', 'published')
            ->where('end_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(4)
            ->get()
            ->map(function (CalendarEvent $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'start_date' => optional($event->start_date)->toIso8601String(),
                    'end_date' => optional($event->end_date)->toIso8601String(),
                    'start_time' => $event->is_all_day ? null : optional($event->start_date)->format('H:i'),
                    'end_time' => $event->is_all_day ? null : optional($event->end_date)->format('H:i'),
                    'is_all_day' => (bool) $event->is_all_day,
                    'location' => $event->location,
                    'event_type' => $event->event_type,
                ];
            })
            ->values();

        $assignmentsQuery = ClassAssignment::query()
            ->with(['classRoom:id,name,code', 'subject:id,name'])
            ->where('business_id', $business->id)
            ->where('status', 'published')
            ->whereDate('due_date', '>=', $today->toDateString())
            ->orderBy('due_date');

        if ($classRoomIds->isNotEmpty()) {
            $assignmentsQuery->whereIn('class_room_id', $classRoomIds);
        }

        $assignmentsQuery->whereDoesntHave('parentHiddenStates', function ($q) use ($user) {
            $q->where('parent_guardian_id', $user->id);
        });

        $assignments = $assignmentsQuery
            ->limit(6)
            ->get()
            ->map(function (ClassAssignment $assignment) {
                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'due_date' => optional($assignment->due_date)->toIso8601String(),
                    'class_room' => $assignment->classRoom?->name,
                    'subject' => $assignment->subject?->name,
                    'assignment_type' => $assignment->assignment_type,
                ];
            })
            ->values();

        $childrenData = $children->map(function (Student $student) {
            return [
                'id' => $student->id,
                'uuid' => $student->uuid,
                'full_name' => $student->full_name,
                'business_id' => $student->business_id,
                'class' => $student->classRoom?->name,
                'class_room_id' => $student->class_room_id,
                'student_id' => $student->student_id,
                'access_code' => $student->ensureAccessCode(),
                'photo_url' => $this->resolvePhotoUrl($student->photo),
                'avatar_url' => "https://ui-avatars.com/api/?name=" . urlencode($student->full_name) . "&background=4A90E2&color=ffffff",
                'allergies' => $student->allergies,
                'has_medical_alert' => $student->hasMedicalAlert(),
            ];
        });

        $childIds = $children->pluck('id')->filter()->values();
        $patientIds = $user->clinicPatients()->pluck('id');

        $pendingFees = ($childIds->isEmpty() && $patientIds->isEmpty())
            ? collect()
            : Fee::query()
                ->with([
                    'business:id,currency_code',
                    'student:id,first_name,last_name,student_id',
                    'clinicPatient:id,first_name,last_name,patient_number',
                    'term:id,name,academic_year',
                    'invoiceDocument',
                    'clinicInvoiceDocument',
                    'payments',
                ])
                ->where(function ($query) use ($business, $childIds, $patientIds) {
                    if ($childIds->isNotEmpty()) {
                        $query->orWhere(function ($school) use ($business, $childIds) {
                            $school->where('business_id', $business->id)
                                ->whereIn('student_id', $childIds)
                                ->whereNull('clinic_patient_id');
                        });
                    }

                    if ($patientIds->isNotEmpty()) {
                        $query->orWhereIn('clinic_patient_id', $patientIds);
                    }
                })
                ->whereIn('payment_status', ['pending', 'partial', 'overdue'])
                ->where('balance', '>', 0)
                ->orderBy('due_date')
                ->limit(8)
                ->get()
                ->map(fn (Fee $fee) => app(ParentFeeController::class)->transform($fee))
                ->values();

        $latestAcademic = $childIds->isEmpty()
            ? null
            : StudentAcademicEntry::query()->whereIn('student_id', $childIds)->max('updated_at');
        $latestCharacter = $childIds->isEmpty()
            ? null
            : StudentCharacterReport::query()->whereIn('student_id', $childIds)->max('updated_at');
        $progressRevision = collect([$latestAcademic, $latestCharacter, $childIds->implode(',')])
            ->filter()
            ->implode('|');

        $memoryItems = MemoryWallItem::visibleForBusinesses($churchIds);

        $pickupCodes = $childIds->isEmpty()
            ? collect()
            : PickupCode::query()
                ->whereIn('student_id', $childIds)
                ->whereDate('code_date', $today->toDateString())
                ->whereNull('used_at')
                ->get()
                ->map(fn (PickupCode $code) => [
                    'student_id' => $code->student_id,
                    'code' => $code->code,
                    'expires_at' => optional($code->expires_at)->toIso8601String(),
                ])
                ->values();

        $lessons = collect();
        if (Schema::hasTable('kids_lessons')) {
            $lessons = KidsLesson::query()
                ->whereIn('business_id', $churchIds)
                ->published()
                ->orderByDesc('lesson_date')
                ->orderByDesc('id')
                ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Parent dashboard data loaded successfully.',
            'data' => [
                'parent_profile' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'photo_url' => $this->resolvePhotoUrl($user->photo),
                ],
                'is_church' => $business->isChurch(),
                'is_school' => $business->isSchool(),
                'business_id' => $business->id,
                'business_name' => $business->name,
                'enabled_feature_names' => $business->enabledFeatureNames(),
                'linked_organizations' => $linkedOrganizations,
                'children' => $childrenData,
                'announcements' => $announcements,
                'upcoming_events' => $events,
                'upcoming_assignments' => $assignments,
                'pending_fees' => $pendingFees,
                'progress_revision' => $progressRevision ?: null,
                'memory_verse' => $memoryItems->firstWhere('type', 'memory_verse'),
                'prayer_focus' => $memoryItems->firstWhere('type', 'prayer_focus'),
                'pickup_codes' => $pickupCodes,
                'this_week_lesson' => $this->transformLesson($lessons->firstWhere('type', 'bible_lesson')),
                'home_resource' => $this->transformLesson($lessons->firstWhere('type', 'home_resource')),
                'pastor_devotional' => $this->transformLesson($lessons->firstWhere('type', 'pastor_devotional')),
            ],
        ]);
    }

    private function linkedOrganizations(ParentGuardian $user, $currentBusiness): array
    {
        $organizations = $user->activeBusinesses()->get();

        if ($organizations->isEmpty() && $currentBusiness) {
            $organizations = collect([$currentBusiness]);
        } elseif ($currentBusiness && ! $organizations->contains(fn ($org) => (int) $org->id === (int) $currentBusiness->id)) {
            $organizations = $organizations->prepend($currentBusiness);
        }

        return $organizations
            ->unique('id')
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'type' => $org->type,
                'is_school' => $org->isSchool(),
                'is_church' => $org->isChurch(),
                'enabled_feature_names' => $org->enabledFeatureNames(),
            ])
            ->values()
            ->all();
    }

    private function transformLesson($lesson): ?array
    {
        if (! $lesson instanceof KidsLesson) {
            return null;
        }

        return [
            'uuid' => $lesson->uuid,
            'type' => $lesson->type,
            'title' => $lesson->title,
            'body' => $lesson->body,
            'family_challenge' => $lesson->family_challenge,
            'memory_verse' => $lesson->memory_verse,
            'scripture_ref' => $lesson->scripture_ref,
        ];
    }

    private function resolvePhotoUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
