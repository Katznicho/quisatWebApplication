<?php

namespace App\Http\Controllers\SchoolManagement;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Services\ParentUniversalCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ParentGuardianController extends Controller
{
    public function __construct(
        protected ParentUniversalCodeService $codes
    ) {}

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
     * Merges by normalized email or phone when the parent already exists.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
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
        $businessId = $business->id ?? null;

        if (! $businessId) {
            return redirect()->route('school-management.parents')
                ->with('error', 'No business associated with your account.');
        }

        $email = $this->codes->normalizeEmail($validated['email']);
        $existing = $this->codes->findByNormalizedEmail($email, true)
            ?? $this->codes->findByNormalizedPhone($validated['phone'], true);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('parent-guardians', 'public');
        }

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $updates = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $email,
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? $existing->address,
                'city' => $validated['city'] ?? $existing->city,
                'country' => $validated['country'] ?? $existing->country,
                'relationship' => $validated['relationship'],
                'occupation' => $validated['occupation'] ?? $existing->occupation,
                'emergency_contact' => $validated['emergency_contact'] ?? $existing->emergency_contact,
                'status' => $validated['status'] ?? 'active',
            ];

            if ($photoPath) {
                $updates['photo'] = $photoPath;
            }

            // Never overwrite an existing password.
            $existing->fill($updates)->save();

            $this->codes->attachToBusiness(
                $existing,
                (int) $businessId,
                'staff_create',
                $validated['relationship']
            );

            return redirect()->route('school-management.parents')
                ->with('success', 'Existing parent linked to this business successfully!');
        }

        $validated['email'] = $email;
        $validated['business_id'] = $businessId;
        $validated['account_type'] = 'linked';
        $validated['status'] = $validated['status'] ?? 'active';
        if ($photoPath) {
            $validated['photo'] = $photoPath;
        }

        $parent = ParentGuardian::create($validated);

        $this->codes->attachToBusiness(
            $parent,
            (int) $businessId,
            'staff_create',
            $validated['relationship']
        );

        return redirect()->route('school-management.parents')
            ->with('success', 'Parent/Guardian created successfully!');
    }

    /**
     * Show the form for editing the specified parent/guardian.
     */
    public function edit(ParentGuardian $parent)
    {
        $business = Auth::user()->business;
        $businessId = $business->id ?? null;

        if (! $parent) {
            abort(404);
        }

        if (! $this->canManageParent($parent, $businessId)) {
            abort(403, 'Unauthorized');
        }

        return view('school-management.parents.edit', compact('parent'));
    }

    /**
     * Update the specified parent/guardian in storage.
     */
    public function update(Request $request, ParentGuardian $parent)
    {
        $business = Auth::user()->business;
        $businessId = $business->id ?? null;

        if (! $this->canManageParent($parent, $businessId)) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:parent_guardians,email,'.$parent->id.'|max:255',
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

        $photoPath = $parent->photo;
        if ($request->hasFile('photo')) {
            if (! empty($photoPath) && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('parent-guardians', 'public');
        }

        $data = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $this->codes->normalizeEmail($validated['email']),
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'relationship' => $validated['relationship'],
            'occupation' => $validated['occupation'] ?? null,
            'emergency_contact' => $validated['emergency_contact'] ?? null,
            'status' => $validated['status'],
            'photo' => $photoPath,
            // Keep business_id unchanged; it is not exposed on the form.
            'business_id' => $parent->business_id,
        ];

        $parent->update($data);

        return redirect()->route('school-management.parents')
            ->with('success', 'Parent/Guardian updated successfully!');
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

        $filename = 'parent_guardian_template_'.now()->format('Y-m-d').'.csv';

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
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

        if (! $businessId) {
            return redirect()->route('school-management.parents')
                ->with('error', 'No business associated with your account.');
        }

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
                $errors[] = "Row {$rowNumber}: ".implode(', ', $validator->errors()->all());

                continue;
            }

            $email = $this->codes->normalizeEmail($data['email']);
            $data['email'] = $email;
            $data['status'] = $data['status'] ?? 'active';

            try {
                $existing = $this->codes->findByNormalizedEmail($email, true)
                    ?? $this->codes->findByNormalizedPhone($data['phone'], true);

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    // Preserve password; refresh profile fields from CSV.
                    $existing->fill([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $email,
                        'phone' => $data['phone'],
                        'address' => $data['address'] ?? $existing->address,
                        'city' => $data['city'] ?? $existing->city,
                        'country' => $data['country'] ?? $existing->country,
                        'relationship' => $data['relationship'],
                        'occupation' => $data['occupation'] ?? $existing->occupation,
                        'emergency_contact' => $data['emergency_contact'] ?? $existing->emergency_contact,
                        'status' => $data['status'],
                    ])->save();

                    $this->codes->attachToBusiness(
                        $existing,
                        (int) $businessId,
                        'staff_create',
                        $data['relationship']
                    );
                } else {
                    $data['business_id'] = $businessId;
                    $data['account_type'] = 'linked';
                    $parent = ParentGuardian::create($data);
                    $this->codes->attachToBusiness(
                        $parent,
                        (int) $businessId,
                        'staff_create',
                        $data['relationship']
                    );
                }

                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: ".$e->getMessage();
            }
        }

        $message = "Bulk upload completed. Success: {$successCount}, Errors: {$errorCount}";
        if (! empty($errors)) {
            $message .= "\n\nErrors:\n".implode("\n", array_slice($errors, 0, 10)); // Show first 10 errors
            if (count($errors) > 10) {
                $message .= "\n... and ".(count($errors) - 10).' more errors.';
            }
        }

        return redirect()->route('school-management.parents')
            ->with('success', $message)
            ->with('bulk_upload_errors', $errors);
    }

    protected function canManageParent(ParentGuardian $parent, ?int $businessId): bool
    {
        if (! $businessId) {
            return false;
        }

        // Super business may administer any parent.
        if ((int) $businessId === 1) {
            return true;
        }

        return $parent->belongsToBusiness((int) $businessId);
    }
}
