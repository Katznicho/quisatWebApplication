<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAcademicEntry;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;

class StudentAcademicEntryController extends Controller
{
    /**
     * List academic entries for a student (latest first).
     */
    public function index(Request $request, Student $student)
    {
        $business = $request->get('business');

        if ($student->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in your business.',
            ], 404);
        }

        $limit = (int) $request->query('limit', 30);
        $limit = $limit > 0 ? min($limit, 100) : 30;

        $entries = StudentAcademicEntry::query()
            ->with('subject:id,name,code')
            ->where('business_id', $business->id)
            ->where('student_id', $student->id)
            ->orderByDesc('record_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (StudentAcademicEntry $entry) {
                return [
                    'id' => $entry->id,
                    'uuid' => $entry->uuid,
                    'record_date' => optional($entry->record_date)->toDateString(),
                    'subject_id' => $entry->subject_id,
                    'subject' => $entry->subject?->name,
                    'percentage' => (float) $entry->percentage,
                    'notes' => $entry->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Academic entries loaded successfully.',
            'data' => [
                'entries' => $entries,
            ],
        ]);
    }

    /**
     * Create an academic progress entry for a student (staff).
     */
    public function store(Request $request, Student $student)
    {
        $business = $request->get('business');
        $user = $request->get('authenticated_user');

        if ($student->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in your business.',
            ], 404);
        }

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Only staff can record academic progress.',
            ], 403);
        }

        $validated = $request->validate([
            'record_date' => 'required|date',
            'subject_id' => 'required|exists:subjects,id',
            'percentage' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $subject = \App\Models\Subject::where('id', $validated['subject_id'])
            ->where('business_id', $business->id)
            ->first();

        if (! $subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found in your business.',
            ], 404);
        }

        $term = Term::where('business_id', $business->id)->where('is_current_term', true)->first();

        $entry = StudentAcademicEntry::create([
            'business_id' => $business->id,
            'student_id' => $student->id,
            'subject_id' => $validated['subject_id'],
            'term_id' => $term?->id,
            'record_date' => $validated['record_date'],
            'percentage' => $validated['percentage'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Academic progress recorded successfully.',
            'data' => [
                'entry' => [
                    'id' => $entry->id,
                    'uuid' => $entry->uuid,
                    'record_date' => $entry->record_date->toDateString(),
                    'subject_id' => $entry->subject_id,
                    'subject' => $entry->subject?->name,
                    'percentage' => (float) $entry->percentage,
                    'notes' => $entry->notes,
                ],
            ],
        ]);
    }
}
