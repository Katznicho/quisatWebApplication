<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        #fecth all users
        $users = User::all();
        // Pass businesses to populate select dropdown (optional: only if admin)
        $businesses = Business::all();

        return view('users.index', compact('users', 'businesses'));
    }



    public function update(Request $request, User $user)
    {
        $request->validate([
            'balance' => 'required|numeric',
            'profit' => 'required|numeric',
            'total_trades' => 'required|integer',
        ]);

        $user->update([
            'balance' => $request->balance,
            'profit' => $request->profit,
            'total_trades' => $request->total_trades,
        ]);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $businessId = Auth::user()->business_id;

        // Get businesses for dropdown (only if admin)
        $businesses = [];
        if ($businessId === 1) {
            $businesses = Business::all()->mapWithKeys(function ($business) {
                return [$business->id => $business->name];
            });
        }

        // Get roles for dropdown (filtered by business)
        $roles = \App\Models\Role::where('business_id', $businessId)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($role) {
                return [$role->id => $role->name];
            });

        // Get branches for dropdown (filtered by business)
        $branches = \App\Models\Branch::where('business_id', $businessId)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($branch) {
                return [$branch->id => $branch->name];
            });

        return view('users.create', compact('businesses', 'roles', 'branches', 'businessId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $businessId = Auth::user()->business_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,suspended',
            'business_id' => $businessId === 1 ? 'required|exists:businesses,id' : 'nullable',
            'branch_id' => 'nullable|exists:branches,id',
            'role_id' => 'required|exists:roles,id',
            'profile_photo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            // Set business_id if not admin
            if ($businessId !== 1) {
                $validated['business_id'] = $businessId;
            }

            // Upload profile photo if provided
            if ($request->hasFile('profile_photo_path')) {
                $validated['profile_photo_path'] = $request->file('profile_photo_path')->store('profile_photos', 'public');
            }

            // Create the user WITHOUT a password
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
                'business_id' => $validated['business_id'],
                'branch_id' => $validated['branch_id'] ?? null,
                'role_id' => $validated['role_id'],
                'profile_photo_path' => $validated['profile_photo_path'] ?? null,
                'password' => '', // Empty password
            ]);

            // Send password setup link (uses Laravel's password reset logic)
            Password::sendResetLink(['email' => $user->email]);

            return redirect()->route('users.index')->with('success', 'User created successfully. A password setup link has been sent to their email.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Show the bulk upload page.
     */
    public function bulkUploadPage()
    {
        return view('users.bulk-upload');
    }

    /**
     * Download CSV template for bulk upload.
     */
    public function downloadTemplate()
    {
        // Create CSV content with headers and example rows
        $csvData = "name,email,phone,status,role_name,branch_name\n";
        $csvData .= "John Doe,john.doe@example.com,+256700000000,active,Admin,Main Branch\n";
        $csvData .= "Jane Smith,jane.smith@example.com,+256700000001,active,Staff,Main Branch\n";

        $filename = 'user_template_' . now()->format('Y-m-d') . '.csv';

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
        $businessId = Auth::user()->business_id;

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
                    'name' => 'name',
                    'email' => 'email',
                    'phone' => 'phone',
                    'telephone' => 'phone',
                    'mobile' => 'phone',
                    'status' => 'status',
                    'role_name' => 'role_name',
                    'role' => 'role_name',
                    'branch_name' => 'branch_name',
                    'branch' => 'branch_name',
                ];

                if (isset($fieldMap[$header])) {
                    $data[$fieldMap[$header]] = $value;
                }
            }

            // Validate required fields
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'status' => 'required|in:active,inactive,suspended',
                'role_name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                continue;
            }

            // Check if email already exists
            if (User::where('email', $data['email'])->exists()) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: Email already exists: {$data['email']}";
                continue;
            }

            // Find role by name
            $role = \App\Models\Role::where('name', $data['role_name'])
                ->where('business_id', $businessId)
                ->first();

            if (!$role) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: Role not found: {$data['role_name']}. Please create the role first.";
                continue;
            }

            // Find branch by name if provided
            $branchId = null;
            if (!empty($data['branch_name'])) {
                $branch = \App\Models\Branch::where('name', $data['branch_name'])
                    ->where('business_id', $businessId)
                    ->first();

                if (!$branch) {
                    $errorCount++;
                    $errors[] = "Row {$rowNumber}: Branch not found: {$data['branch_name']}. Please create the branch first.";
                    continue;
                }
                $branchId = $branch->id;
            }

            // Set defaults
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'role_id' => $role->id,
                'password' => '', // Empty password - will send reset link
            ];

            try {
                $user = User::create($userData);
                // Send password setup link
                Password::sendResetLink(['email' => $user->email]);
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

        return redirect()->route('users.index')
            ->with('success', $message)
            ->with('bulk_upload_errors', $errors);
    }


    /**
     * Display the specified resource.
     */
    // public function show(string $id)
    // {
    //     //
    //     return view('users.show');
    // }

    public function show(User $user)
{
    // Works automatically thanks to route model binding on slug
    return view('users.show', compact('user'));
}


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        return view('users.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
