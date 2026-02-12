<?php

namespace App\Http\Controllers\SchoolManagement;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ParentGuardianController extends Controller
{
    /**
     * Show the form for creating a new parent/guardian.
     */
    public function create()
    {
        return view('school-management.parents.create');
    }

    /**
     * Show the bulk upload page.
     */
    public function bulkUploadPage()
    {
        return view('school-management.parents.bulk-upload');
    }

    /**
     * Store a newly created parent/guardian in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:parent_guardians,email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'relationship' => 'required|in:father,mother,guardian,other',
            'occupation' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $business = Auth::user()->business;
        $validated['business_id'] = $business->id ?? null;
        $validated['status'] = $validated['status'] ?? 'active';

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('parent-guardians', 'public');
        }

        ParentGuardian::create($validated);

        return redirect()->route('school-management.parents')
            ->with('success', 'Parent/Guardian created successfully!');
    }

    /**
     * Download CSV template for bulk upload.
     */
    public function downloadTemplate()
    {
        // Create CSV content with headers and example rows
        $csvData = "first_name,last_name,email,phone,relationship,address,city,country,occupation,emergency_contact,status\n";
        $csvData .= "John,Doe,john.doe@example.com,+256700000000,father,123 Main Street,Kampala,Uganda,Engineer,+256700000001,active\n";
        $csvData .= "Jane,Smith,jane.smith@example.com,+256700000002,mother,456 Oak Avenue,Entebbe,Uganda,Teacher,+256700000003,active\n";

        $filename = 'parent_guardian_template_' . now()->format('Y-m-d') . '.csv';

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
                    'address' => 'address',
                    'city' => 'city',
                    'country' => 'country',
                    'relationship' => 'relationship',
                    'occupation' => 'occupation',
                    'emergency_contact' => 'emergency_contact',
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
                'phone' => 'required|string|max:255',
                'relationship' => 'required|in:father,mother,guardian,other',
                'status' => 'nullable|in:active,inactive',
            ]);

            if ($validator->fails()) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                continue;
            }

            // Check if email already exists
            if (ParentGuardian::where('email', $data['email'])->exists()) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: Email already exists: {$data['email']}";
                continue;
            }

            // Set defaults
            $data['business_id'] = $businessId;
            $data['status'] = $data['status'] ?? 'active';

            try {
                ParentGuardian::create($data);
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

        return redirect()->route('school-management.parents')
            ->with('success', $message)
            ->with('bulk_upload_errors', $errors);
    }
}
