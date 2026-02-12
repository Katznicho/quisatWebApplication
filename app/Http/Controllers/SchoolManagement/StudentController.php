<?php

namespace App\Http\Controllers\SchoolManagement;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\ParentGuardian;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Show the form for creating a new student.
     */
    public function create()
    {
        $business = Auth::user()->business;
        $businessId = $business->id ?? null;

        // Get parent/guardians for dropdown
        $parentGuardians = ParentGuardian::where('business_id', $businessId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(function ($parent) {
                return [$parent->id => $parent->full_name];
            });

        // Get classrooms for dropdown
        $classRooms = ClassRoom::where('business_id', $businessId)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($classRoom) {
                return [$classRoom->id => $classRoom->name];
            });

        return view('school-management.students.create', compact('parentGuardians', 'classRooms'));
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email|max:255',
            'phone' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'student_id' => 'required|string|unique:students,student_id|max:255',
            'admission_date' => 'required|date',
            'parent_guardian_id' => 'required|exists:parent_guardians,id',
            'class_room_id' => 'nullable|exists:class_rooms,id',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,graduated,transferred',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $business = Auth::user()->business;
        $validated['business_id'] = $business->id ?? null;
        $validated['status'] = $validated['status'] ?? 'active';

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        Student::create($validated);

        return redirect()->route('school-management.students')
            ->with('success', 'Student created successfully!');
    }

    /**
     * Show the bulk upload page.
     */
    public function bulkUploadPage()
    {
        return view('school-management.students.bulk-upload');
    }

    /**
     * Download CSV template for bulk upload.
     */
    public function downloadTemplate()
    {
        // Create CSV content with headers and example rows
        $csvData = "first_name,last_name,email,phone,date_of_birth,gender,student_id,admission_date,parent_email,address,city,country,status\n";
        $csvData .= "John,Doe,john.doe@example.com,+256700000000,2010-05-15,male,STU001,2024-01-15,parent1@example.com,123 Main Street,Kampala,Uganda,active\n";
        $csvData .= "Jane,Smith,jane.smith@example.com,+256700000001,2011-08-20,female,STU002,2024-01-15,parent2@example.com,456 Oak Avenue,Entebbe,Uganda,active\n";

        $filename = 'student_template_' . now()->format('Y-m-d') . '.csv';

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Handle bulk upload via CSV file.
     */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $business = Auth::user()->business;
        $businessId = $business->id ?? null;

        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_shift($csvData); // Remove header row

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map CSV columns to database fields
            $data = [];
            foreach ($headers as $key => $header) {
                $header = strtolower(trim($header));
                $value = isset($row[$key]) ? trim($row[$key]) : '';

                // Map common CSV column names to database fields
                $fieldMap = [
                    'first_name' => 'first_name',
                    'firstname' => 'first_name',
                    'fname' => 'first_name',
                    'last_name' => 'last_name',
                    'lastname' => 'last_name',
                    'lname' => 'last_name',
                    'email' => 'email',
                    'phone' => 'phone',
                    'telephone' => 'phone',
                    'mobile' => 'phone',
                    'date_of_birth' => 'date_of_birth',
                    'dob' => 'date_of_birth',
                    'birthdate' => 'date_of_birth',
                    'gender' => 'gender',
                    'student_id' => 'student_id',
                    'admission_date' => 'admission_date',
                    'admission' => 'admission_date',
                    'parent_email' => 'parent_email',
                    'parent_guardian_email' => 'parent_email',
                    'address' => 'address',
                    'city' => 'city',
                    'country' => 'country',
                    'status' => 'status',
                ];

                if (isset($fieldMap[$header])) {
                    $data[$fieldMap[$header]] = $value;
                }
            }

            // Validate required fields
            $validator = Validator::make($data, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'student_id' => 'required|string|max:255',
                'admission_date' => 'required|date',
                'parent_email' => 'required|email|max:255',
                'status' => 'nullable|in:active,inactive,graduated,transferred',
            ]);

            if ($validator->fails()) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                continue;
            }

            // Check if email already exists
            if (Student::where('email', $data['email'])->exists()) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: Email already exists: {$data['email']}";
                continue;
            }

            // Check if student_id already exists
            if (Student::where('student_id', $data['student_id'])->exists()) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: Student ID already exists: {$data['student_id']}";
                continue;
            }

            // Find parent/guardian by email
            $parentGuardian = ParentGuardian::where('email', $data['parent_email'])
                ->where('business_id', $businessId)
                ->first();

            if (!$parentGuardian) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: Parent/Guardian not found with email: {$data['parent_email']}. Please create the parent/guardian first.";
                continue;
            }

            // Set defaults
            $data['business_id'] = $businessId;
            $data['parent_guardian_id'] = $parentGuardian->id;
            $data['status'] = $data['status'] ?? 'active';
            
            // Remove parent_email from data as it's not a database field
            unset($data['parent_email']);

            try {
                Student::create($data);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        $message = "Bulk upload completed. Success: {$successCount}, Errors: {$errorCount}";
        if (!empty($errors)) {
            $message .= "\n\nErrors:\n" . implode("\n", array_slice($errors, 0, 10)); // Show first 10 errors
            if (count($errors) > 10) {
                $message .= "\n... and " . (count($errors) - 10) . " more errors.";
            }
        }

        return redirect()->route('school-management.students')
            ->with('success', $message)
            ->with('bulk_upload_errors', $errors);
    }
}
