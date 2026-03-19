<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentCharacterReport;
use App\Models\User;
use Illuminate\Http\Request;

class StudentCharacterController extends Controller
{
    /**
     * List character reports for a specific student (latest first).
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

        $reports = StudentCharacterReport::query()
            ->where('business_id', $business->id)
            ->where('student_id', $student->id)
            ->orderByDesc('record_date')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(function (StudentCharacterReport $report) {
                return $this->transformReport($report);
            });

        return response()->json([
            'success' => true,
            'message' => 'Student character reports loaded successfully.',
            'data' => [
                'reports' => $reports,
            ],
        ]);
    }

    /**
     * Create or update a character report for a specific student.
     *
     * The staff app can send:
     * - record_date (optional, defaults to today)
     * - status (e.g. Excellent, On Track, Needs Support, At Risk)
     * - headline (short title)
     * - notes (detailed comment)
     * - traits: array of { key, label, rating, comment }
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

        $validated = $request->validate([
            'record_date' => 'nullable|date',
            'status' => 'nullable|string|max:100',
            'headline' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'traits' => 'nullable|array',
            'traits.*.key' => 'required_with:traits|string|max:100',
            'traits.*.label' => 'nullable|string|max:150',
            'traits.*.rating' => 'nullable|string|max:50',
            'traits.*.comment' => 'nullable|string',
            'term_id' => 'nullable|exists:terms,id',
        ]);

        $recordDate = $validated['record_date'] ?? now()->toDateString();

        $report = StudentCharacterReport::updateOrCreate(
            [
                'business_id' => $business->id,
                'student_id' => $student->id,
                'record_date' => $recordDate,
            ],
            [
                'status' => $validated['status'] ?? null,
                'headline' => $validated['headline'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'traits' => $validated['traits'] ?? null,
                'term_id' => $validated['term_id'] ?? null,
                'created_by' => $user instanceof \App\Models\User ? $user->id : null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Student character report saved successfully.',
            'data' => [
                'report' => $this->transformReport($report),
            ],
        ]);
    }

    /**
     * Delete a character report for a specific student (staff only).
     */
    public function destroy(Request $request, Student $student, string $report)
    {
        $business = $request->get('business');
        $viewer = $request->get('authenticated_user');

        if ($student->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in your business.',
            ], 404);
        }

        if (! $this->canManageReports($viewer)) {
            return response()->json([
                'success' => false,
                'message' => 'Only staff users can delete character reports.',
            ], 403);
        }

        $record = StudentCharacterReport::query()
            ->where('business_id', $business->id)
            ->where('student_id', $student->id)
            ->where(function ($query) use ($report) {
                $query->where('id', $report)
                    ->orWhere('uuid', $report);
            })
            ->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Character report not found.',
            ], 404);
        }

        if (method_exists($record, 'forceDelete')) {
            $record->forceDelete();
        } else {
            $record->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Character report deleted successfully.',
        ]);
    }

    /**
     * Transform report to a clean API shape.
     */
    protected function transformReport(StudentCharacterReport $report): array
    {
        return [
            'id' => $report->id,
            'uuid' => $report->uuid,
            'record_date' => optional($report->record_date)->toDateString(),
            'status' => $report->status,
            'headline' => $report->headline,
            'notes' => $report->notes,
            'traits' => $report->traits ?: [],
            'term' => $report->term ? [
                'id' => $report->term->id,
                'name' => $report->term->name,
            ] : null,
            'created_by' => $report->creator ? [
                'id' => $report->creator->id,
                'name' => $report->creator->name,
            ] : null,
            'created_at' => optional($report->created_at)->toIso8601String(),
        ];
    }

    protected function canManageReports($viewer): bool
    {
        if (! $viewer instanceof User) {
            return false;
        }

        if (method_exists($viewer, 'hasRole')) {
            return ! $viewer->hasRole('student');
        }

        return true;
    }
}

