<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StudentProgressController extends Controller
{
    public function show(Request $request, Student $student)
    {
        $business = $request->get('business');

        if ($student->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found in your business.',
            ], 404);
        }

        $grades = Grade::with('subject:id,name')
            ->where('student_id', $student->id)
            ->orderBy('created_at')
            ->get();

        $attendanceRecords = Attendance::where('student_id', $student->id)->get();

        $overallAverage = round($grades->avg('percentage') ?? 0, 2);
        $attendancePercentage = $this->calculateAttendancePercentage($attendanceRecords);

        $monthly = $this->buildPerformanceSeries($grades, 'Y-m');
        $quarterly = $this->buildPerformanceSeries($grades, 'Y-\QQ');
        $annually = $this->buildPerformanceSeries($grades, 'Y');

        return response()->json([
            'success' => true,
            'message' => 'Student progress loaded successfully.',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'class' => $student->classRoom?->name,
                    'avatar_url' => "https://ui-avatars.com/api/?name=" . urlencode($student->full_name) . "&background=4A90E2&color=ffffff",
                ],
                'overview' => [
                    'overall_progress' => $this->labelForAverage($overallAverage),
                    'academic_average' => $overallAverage,
                    'attendance' => $attendancePercentage,
                ],
                'performance' => [
                    'monthly' => $monthly,
                    'quarterly' => $quarterly,
                    'annually' => $annually,
                ],
            ],
        ]);
    }

    protected function buildPerformanceSeries($grades, string $groupFormat)
    {
        $grouped = $grades->groupBy(function (Grade $grade) use ($groupFormat) {
            return optional($grade->created_at)->format($groupFormat);
        });

        $series = [];
        foreach ($grouped as $key => $items) {
            if (!$key) {
                continue;
            }
            $english = $this->averageForSubject($items, 'English');
            $math = $this->averageForSubject($items, 'Mathematics');

            $label = $key;
            if ($groupFormat === 'Y-m') {
                $label = Carbon::createFromFormat('Y-m', $key)->format('M');
            } elseif ($groupFormat === 'Y-\QQ') {
                [$year, $quarter] = explode('-Q', $key);
                $label = 'Q' . $quarter;
            }

            $series[] = [
                'label' => $label,
                'english' => $english,
                'math' => $math,
            ];
        }

        return $series;
    }

    protected function averageForSubject($grades, string $subjectName)
    {
        $subjectGrades = $grades->filter(function (Grade $grade) use ($subjectName) {
            return strcasecmp($grade->subject?->name ?? '', $subjectName) === 0;
        });

        if ($subjectGrades->isEmpty()) {
            return null;
        }

        return round($subjectGrades->avg('percentage'), 2);
    }

    protected function calculateAttendancePercentage($records)
    {
        if ($records->isEmpty()) {
            return null;
        }

        $total = $records->count();
        $present = $records->where('status', 'present')->count();

        if ($total === 0) {
            return null;
        }

        return round(($present / $total) * 100, 2);
    }

    protected function labelForAverage(float $average): string
    {
        if ($average >= 90) {
            return 'Excellent';
        }
        if ($average >= 75) {
            return 'On Track';
        }
        if ($average >= 60) {
            return 'Needs Support';
        }
        return 'At Risk';
    }
}
