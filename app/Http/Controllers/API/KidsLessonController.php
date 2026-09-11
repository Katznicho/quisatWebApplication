<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KidsLesson;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use App\Services\KidsChurchNotificationService;
use Illuminate\Http\Request;

class KidsLessonController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = KidsLesson::query()
            ->with('classRoom:id,name')
            ->where('business_id', $businessId)
            ->orderByDesc('lesson_date')
            ->orderByDesc('id');

        if ($user instanceof ParentGuardian) {
            $classIds = Student::query()
                ->where('parent_guardian_id', $user->id)
                ->where('business_id', $businessId)
                ->pluck('class_room_id')
                ->filter()
                ->unique();

            $query->published()->where(function ($q) use ($classIds) {
                $q->whereNull('class_room_id');
                if ($classIds->isNotEmpty()) {
                    $q->orWhereIn('class_room_id', $classIds);
                }
            });
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $items = $query->paginate(min((int) $request->query('per_page', 30), 50));
        $items->getCollection()->transform(fn (KidsLesson $lesson) => $this->transform($lesson));

        return response()->json([
            'success' => true,
            'data' => ['lessons' => $items->items()],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->get('authenticated_user');
        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'Only staff can publish lessons.'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:bible_lesson,home_resource,pastor_devotional',
            'title' => 'required|string|max:255',
            'class_room_id' => 'nullable|exists:class_rooms,id',
            'body' => 'nullable|string',
            'object_lesson' => 'nullable|string',
            'craft_supplies' => 'nullable|string',
            'teaching_script' => 'nullable|string',
            'memory_verse' => 'nullable|string|max:255',
            'scripture_ref' => 'nullable|string|max:120',
            'family_challenge' => 'nullable|string',
            'video_url' => 'nullable|url|max:500',
            'lesson_date' => 'nullable|date',
            'status' => 'nullable|in:draft,published',
        ]);

        $lesson = KidsLesson::create([
            ...$validated,
            'business_id' => $request->get('business_id'),
            'created_by' => $user->id,
            'status' => $validated['status'] ?? 'published',
            'published_at' => ($validated['status'] ?? 'published') === 'published' ? now() : null,
        ]);

        if ($lesson->status === 'published') {
            app(KidsChurchNotificationService::class)->notifyLesson($lesson);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lesson saved.',
            'data' => ['lesson' => $this->transform($lesson->load('classRoom:id,name'))],
        ], 201);
    }

    protected function transform(KidsLesson $lesson): array
    {
        return [
            'uuid' => $lesson->uuid,
            'type' => $lesson->type,
            'title' => $lesson->title,
            'body' => $lesson->body,
            'object_lesson' => $lesson->object_lesson,
            'craft_supplies' => $lesson->craft_supplies,
            'teaching_script' => $lesson->teaching_script,
            'memory_verse' => $lesson->memory_verse,
            'scripture_ref' => $lesson->scripture_ref,
            'family_challenge' => $lesson->family_challenge,
            'video_url' => $lesson->video_url,
            'lesson_date' => optional($lesson->lesson_date)?->toDateString(),
            'status' => $lesson->status,
            'class_room' => $lesson->classRoom ? [
                'id' => $lesson->classRoom->id,
                'name' => $lesson->classRoom->name,
            ] : null,
        ];
    }
}
