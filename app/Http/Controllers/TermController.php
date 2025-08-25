<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $terms = Term::where('business_id', Auth::user()->business_id)
            ->with(['creator'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('start_date', 'desc')
            ->get();

        $currentTerm = Term::where('business_id', Auth::user()->business_id)
            ->where('is_current_term', true)
            ->first();

        $nextTerm = Term::where('business_id', Auth::user()->business_id)
            ->where('is_next_term', true)
            ->first();

        return view('terms.index', compact('terms', 'currentTerm', 'nextTerm'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $termTypes = [
            'first' => 'First Term',
            'second' => 'Second Term',
            'third' => 'Third Term',
            'summer' => 'Summer Term',
            'special' => 'Special Term',
        ];

        $statuses = [
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return view('terms.create', compact('termTypes', 'statuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:terms,code',
            'description' => 'nullable|string',
            'academic_year' => 'required|string|max:20',
            'academic_year_start' => 'required|integer|min:2000|max:2100',
            'academic_year_end' => 'required|integer|min:2000|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'registration_start_date' => 'nullable|date|before:start_date',
            'registration_end_date' => 'nullable|date|after:registration_start_date|before:start_date',
            'term_type' => 'required|in:first,second,third,summer,special',
            'duration_weeks' => 'required|integer|min:1|max:52',
            'total_instructional_days' => 'required|integer|min:1',
            'total_instructional_hours' => 'required|integer|min:1',
            'is_grading_period' => 'boolean',
            'is_exam_period' => 'boolean',
            'mid_term_start_date' => 'nullable|date|after:start_date|before:end_date',
            'mid_term_end_date' => 'nullable|date|after:mid_term_start_date|before:end_date',
            'final_exam_start_date' => 'nullable|date|after:start_date|before:end_date',
            'final_exam_end_date' => 'nullable|date|after:final_exam_start_date|before:end_date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'is_current_term' => 'boolean',
            'is_next_term' => 'boolean',
            'tuition_fee' => 'nullable|numeric|min:0',
            'other_fees' => 'nullable|numeric|min:0',
            'fee_due_date' => 'nullable|date|after:start_date',
            'late_fee_applicable' => 'boolean',
            'late_fee_amount' => 'nullable|numeric|min:0',
            'late_fee_days' => 'nullable|integer|min:1',
            'holidays' => 'nullable|array',
            'special_events' => 'nullable|array',
            'notes' => 'nullable|string',
            'announcements' => 'nullable|string',
        ]);

        // If this is set as current term, unset any existing current term
        if ($request->boolean('is_current_term')) {
            Term::where('business_id', Auth::user()->business_id)
                ->where('is_current_term', true)
                ->update(['is_current_term' => false]);
        }

        // If this is set as next term, unset any existing next term
        if ($request->boolean('is_next_term')) {
            Term::where('business_id', Auth::user()->business_id)
                ->where('is_next_term', true)
                ->update(['is_next_term' => false]);
        }

        $term = Term::create(array_merge($request->all(), [
            'business_id' => Auth::user()->business_id,
            'created_by' => Auth::id(),
        ]));

        return redirect()->route('terms.index')
            ->with('success', 'Term created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Term $term)
    {
        // Check if user has access to this term
        if ($term->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $term->load(['creator']);

        // Get related data
        $exams = Exam::where('term_id', $term->id)->with(['subject', 'classRoom'])->get();
        $grades = Grade::where('term_id', $term->id)->with(['student', 'exam', 'subject'])->get();
        $attendances = Attendance::where('term_id', $term->id)->with(['student', 'classRoom'])->get();
        $fees = Fee::where('term_id', $term->id)->with(['student'])->get();

        // Calculate statistics
        $stats = [
            'total_exams' => $exams->count(),
            'total_grades' => $grades->count(),
            'total_attendances' => $attendances->count(),
            'total_fees' => $fees->count(),
            'total_fee_amount' => $fees->sum('amount'),
            'total_fee_paid' => $fees->sum('amount_paid'),
            'total_fee_balance' => $fees->sum('balance'),
        ];

        return view('terms.show', compact('term', 'exams', 'grades', 'attendances', 'fees', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Term $term)
    {
        // Check if user has access to this term
        if ($term->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $termTypes = [
            'first' => 'First Term',
            'second' => 'Second Term',
            'third' => 'Third Term',
            'summer' => 'Summer Term',
            'special' => 'Special Term',
        ];

        $statuses = [
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return view('terms.edit', compact('term', 'termTypes', 'statuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Term $term)
    {
        // Check if user has access to this term
        if ($term->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:terms,code,' . $term->id,
            'description' => 'nullable|string',
            'academic_year' => 'required|string|max:20',
            'academic_year_start' => 'required|integer|min:2000|max:2100',
            'academic_year_end' => 'required|integer|min:2000|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'registration_start_date' => 'nullable|date|before:start_date',
            'registration_end_date' => 'nullable|date|after:registration_start_date|before:start_date',
            'term_type' => 'required|in:first,second,third,summer,special',
            'duration_weeks' => 'required|integer|min:1|max:52',
            'total_instructional_days' => 'required|integer|min:1',
            'total_instructional_hours' => 'required|integer|min:1',
            'is_grading_period' => 'boolean',
            'is_exam_period' => 'boolean',
            'mid_term_start_date' => 'nullable|date|after:start_date|before:end_date',
            'mid_term_end_date' => 'nullable|date|after:mid_term_start_date|before:end_date',
            'final_exam_start_date' => 'nullable|date|after:start_date|before:end_date',
            'final_exam_end_date' => 'nullable|date|after:final_exam_start_date|before:end_date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'is_current_term' => 'boolean',
            'is_next_term' => 'boolean',
            'tuition_fee' => 'nullable|numeric|min:0',
            'other_fees' => 'nullable|numeric|min:0',
            'fee_due_date' => 'nullable|date|after:start_date',
            'late_fee_applicable' => 'boolean',
            'late_fee_amount' => 'nullable|numeric|min:0',
            'late_fee_days' => 'nullable|integer|min:1',
            'holidays' => 'nullable|array',
            'special_events' => 'nullable|array',
            'notes' => 'nullable|string',
            'announcements' => 'nullable|string',
        ]);

        // If this is set as current term, unset any existing current term
        if ($request->boolean('is_current_term')) {
            Term::where('business_id', Auth::user()->business_id)
                ->where('is_current_term', true)
                ->where('id', '!=', $term->id)
                ->update(['is_current_term' => false]);
        }

        // If this is set as next term, unset any existing next term
        if ($request->boolean('is_next_term')) {
            Term::where('business_id', Auth::user()->business_id)
                ->where('is_next_term', true)
                ->where('id', '!=', $term->id)
                ->update(['is_next_term' => false]);
        }

        $term->update($request->all());

        return redirect()->route('terms.index')
            ->with('success', 'Term updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Term $term)
    {
        // Check if user has access to this term
        if ($term->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        // Check if term has related data
        $hasRelatedData = Exam::where('term_id', $term->id)->exists() ||
                         Grade::where('term_id', $term->id)->exists() ||
                         Attendance::where('term_id', $term->id)->exists() ||
                         Fee::where('term_id', $term->id)->exists();

        if ($hasRelatedData) {
            return redirect()->route('terms.index')
                ->with('error', 'Cannot delete term. It has related data (exams, grades, attendance, or fees).');
        }

        $term->delete();

        return redirect()->route('terms.index')
            ->with('success', 'Term deleted successfully.');
    }

    /**
     * Activate a term
     */
    public function activate(Term $term)
    {
        // Check if user has access to this term
        if ($term->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        // Deactivate current term if exists
        Term::where('business_id', Auth::user()->business_id)
            ->where('is_current_term', true)
            ->update(['is_current_term' => false]);

        // Activate this term
        $term->update([
            'status' => 'active',
            'is_current_term' => true,
        ]);

        return redirect()->route('terms.index')
            ->with('success', 'Term activated successfully.');
    }

    /**
     * Complete a term
     */
    public function complete(Term $term)
    {
        // Check if user has access to this term
        if ($term->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $term->update([
            'status' => 'completed',
            'is_current_term' => false,
        ]);

        return redirect()->route('terms.index')
            ->with('success', 'Term completed successfully.');
    }

    /**
     * Get current term
     */
    public function current()
    {
        $currentTerm = Term::where('business_id', Auth::user()->business_id)
            ->where('is_current_term', true)
            ->with(['creator'])
            ->first();

        if (!$currentTerm) {
            return redirect()->route('terms.index')
                ->with('warning', 'No active term found.');
        }

        return $this->show($currentTerm);
    }

    /**
     * Get term statistics
     */
    public function statistics(Term $term)
    {
        // Check if user has access to this term
        if ($term->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $stats = [
            'exams' => Exam::where('term_id', $term->id)->count(),
            'grades' => Grade::where('term_id', $term->id)->count(),
            'attendances' => Attendance::where('term_id', $term->id)->count(),
            'fees' => Fee::where('term_id', $term->id)->count(),
            'total_fee_amount' => Fee::where('term_id', $term->id)->sum('amount'),
            'total_fee_paid' => Fee::where('term_id', $term->id)->sum('amount_paid'),
            'total_fee_balance' => Fee::where('term_id', $term->id)->sum('balance'),
        ];

        return response()->json($stats);
    }
}
