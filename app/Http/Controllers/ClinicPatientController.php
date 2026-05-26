<?php

namespace App\Http\Controllers;

use App\Models\ClinicFamily;
use App\Models\ClinicFamilyMember;
use App\Models\ClinicPatient;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Services\ClinicPatientImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ClinicPatientController extends Controller
{
    public function __construct(
        protected ClinicPatientImportService $importService
    ) {}

    public function index(Request $request)
    {
        $business = Auth::user()->business;
        $search = strtoupper(trim((string) $request->query('q', '')));

        $patients = ClinicPatient::where('business_id', $business->id ?? 0)
            ->with(['family', 'parentGuardian', 'student'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('school_access_code', $search)
                        ->orWhere('patient_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->orderByDesc('created_at')
            ->get();

        return view('clinic-patients.index', compact('patients', 'search'));
    }

    public function create(Request $request)
    {
        $accessCode = strtoupper(trim((string) $request->query('access_code', '')));
        $previewStudent = null;
        $lookupError = null;
        $alreadyRegistered = false;
        $existingPatient = null;

        if ($accessCode !== '') {
            $previewStudent = Student::with(['business:id,name,city', 'parentGuardian', 'classRoom:id,name'])
                ->where('access_code', $accessCode)
                ->first();

            if (! $previewStudent) {
                $lookupError = 'No child found with that access code. Ask the parent to open the Quisat app → Kids Clinics and share the code for this child.';
            } else {
                $business = Auth::user()->business;
                $existingPatient = ClinicPatient::where('business_id', $business->id ?? 0)
                    ->where('student_id', $previewStudent->id)
                    ->first();
                $alreadyRegistered = (bool) $existingPatient;
            }
        }

        return view('clinic-patients.create', compact(
            'accessCode',
            'previewStudent',
            'lookupError',
            'alreadyRegistered',
            'existingPatient'
        ));
    }

    public function store(Request $request)
    {
        $business = Auth::user()->business;

        if (! $business) {
            abort(403);
        }

        $validated = $request->validate([
            'child_access_code' => 'required|string|max:20',
        ]);

        $code = strtoupper(trim($validated['child_access_code']));

        $student = Student::where('access_code', $code)->first();

        if (! $student) {
            return redirect()
                ->route('clinic-patients.create', ['access_code' => $code])
                ->with('error', 'Invalid access code. Please search again.');
        }

        try {
            $patient = $this->importService->attachStudentToClinic($student, $business);
            $wasNew = $patient->wasRecentlyCreated;

            return redirect()
                ->route('clinic-patients.show', $patient)
                ->with('success', $wasNew
                    ? 'Patient imported from school successfully.'
                    : 'This child was already registered at your clinic.');
        } catch (\Exception $e) {
            return redirect()
                ->route('clinic-patients.create', ['access_code' => $code])
                ->with('error', 'Could not import patient: '.$e->getMessage());
        }
    }

    public function show(ClinicPatient $clinic_patient)
    {
        $this->authorizePatient($clinic_patient);

        $clinic_patient->load([
            'family.members.parentGuardian',
            'parentGuardian',
            'student.business',
            'student.classRoom',
            'appointments' => fn ($q) => $q->orderByDesc('scheduled_at'),
        ]);

        return view('clinic-patients.show', ['patient' => $clinic_patient]);
    }

    public function edit(ClinicPatient $clinic_patient)
    {
        $this->authorizePatient($clinic_patient);

        $business = Auth::user()->business;
        $businessId = $business->id ?? 0;

        $parents = ParentGuardian::where('business_id', $businessId)->orderBy('first_name')->get();
        $families = ClinicFamily::where('business_id', $businessId)->orderBy('family_name')->get();
        $students = Student::where('business_id', $businessId)->orderBy('first_name')->get();

        return view('clinic-patients.edit', [
            'patient' => $clinic_patient,
            'parents' => $parents,
            'families' => $families,
            'students' => $students,
        ]);
    }

    public function update(Request $request, ClinicPatient $clinic_patient)
    {
        $this->authorizePatient($clinic_patient);

        $business = Auth::user()->business;
        $businessId = $business->id ?? 0;

        $validated = $request->validate($this->patientFormRules(forCreate: false));

        if (! $clinic_patient->student_id) {
            $this->assertParentBelongsToBusiness($validated['parent_guardian_id'] ?? null, $businessId);
            $this->assertStudentBelongsToBusiness($validated['student_id'] ?? null, $businessId);
        }
        $this->assertFamilyBelongsToBusiness((int) $validated['clinic_family_id'], $businessId);

        if ($request->hasFile('photo')) {
            if ($clinic_patient->photo && Storage::disk('public')->exists($clinic_patient->photo)) {
                Storage::disk('public')->delete($clinic_patient->photo);
            }
            $validated['photo'] = $request->file('photo')->store('clinic-patients', 'public');
        } else {
            unset($validated['photo']);
        }

        $clinic_patient->update([
            'clinic_family_id' => $validated['clinic_family_id'],
            'parent_guardian_id' => $validated['parent_guardian_id'] ?? null,
            'student_id' => $validated['student_id'] ?? null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'allergies' => $this->normalizeAllergies($request),
            'emergency_contacts' => $this->normalizeEmergencyContacts($request),
            'insurance_info' => $this->buildInsuranceInfo($validated),
            'status' => $validated['status'] ?? $clinic_patient->status,
            'photo' => $validated['photo'] ?? $clinic_patient->photo,
        ]);

        if (! empty($validated['parent_guardian_id'])) {
            $family = ClinicFamily::find($validated['clinic_family_id']);
            if ($family) {
                $this->ensureFamilyMember($family, (int) $validated['parent_guardian_id'], false);
            }
        }

        return redirect()->route('clinic-patients.show', $clinic_patient)
            ->with('success', 'Clinic patient updated successfully!');
    }

    public function destroy(ClinicPatient $clinic_patient)
    {
        $this->authorizePatient($clinic_patient);

        if ($clinic_patient->photo && Storage::disk('public')->exists($clinic_patient->photo)) {
            Storage::disk('public')->delete($clinic_patient->photo);
        }

        $clinic_patient->delete();

        return redirect()->route('clinic-patients.index')
            ->with('success', 'Clinic patient removed successfully.');
    }

    protected function authorizePatient(ClinicPatient $patient): void
    {
        $business = Auth::user()->business;

        if (! $business || $patient->business_id !== $business->id) {
            abort(403, 'You are not allowed to manage this patient.');
        }
    }

    protected function resolveFamily(Request $request, int $businessId, ?int $parentId): ClinicFamily
    {
        if ($request->filled('clinic_family_id')) {
            $family = ClinicFamily::where('business_id', $businessId)
                ->whereKey($request->clinic_family_id)
                ->firstOrFail();

            if ($parentId && ! $family->primary_parent_guardian_id) {
                $family->update(['primary_parent_guardian_id' => $parentId]);
            }

            return $family;
        }

        return ClinicFamily::create([
            'business_id' => $businessId,
            'family_name' => $request->input('family_name') ?: null,
            'primary_parent_guardian_id' => $parentId,
            'status' => 'active',
        ]);
    }

    protected function ensureFamilyMember(ClinicFamily $family, int $parentId, bool $isPrimary): void
    {
        ClinicFamilyMember::firstOrCreate(
            [
                'clinic_family_id' => $family->id,
                'parent_guardian_id' => $parentId,
            ],
            [
                'relationship' => 'guardian',
                'is_primary' => $isPrimary,
            ]
        );

        if ($isPrimary && ! $family->primary_parent_guardian_id) {
            $family->update(['primary_parent_guardian_id' => $parentId]);
        }
    }

    protected function assertParentBelongsToBusiness(?int $parentId, int $businessId): void
    {
        if (! $parentId) {
            return;
        }

        $exists = ParentGuardian::where('id', $parentId)->where('business_id', $businessId)->exists();

        if (! $exists) {
            abort(422, 'Selected parent does not belong to your business.');
        }
    }

    protected function assertStudentBelongsToBusiness(?int $studentId, int $businessId): void
    {
        if (! $studentId) {
            return;
        }

        $exists = Student::where('id', $studentId)->where('business_id', $businessId)->exists();

        if (! $exists) {
            abort(422, 'Selected student does not belong to your business.');
        }
    }

    protected function assertFamilyBelongsToBusiness(int $familyId, int $businessId): void
    {
        $exists = ClinicFamily::where('id', $familyId)->where('business_id', $businessId)->exists();

        if (! $exists) {
            abort(422, 'Selected family does not belong to your business.');
        }
    }

    protected function patientFormRules(bool $forCreate = true): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'blood_group' => 'nullable|string|max:10',
            'allergies' => 'nullable|array',
            'allergies.*' => 'nullable|string|max:255',
            'emergency_contacts' => 'nullable|array',
            'emergency_contacts.*.name' => 'nullable|string|max:255',
            'emergency_contacts.*.phone' => 'nullable|string|max:50',
            'emergency_contacts.*.relationship' => 'nullable|string|max:50',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:255',
            'parent_guardian_id' => 'nullable|exists:parent_guardians,id',
            'student_id' => 'nullable|exists:students,id',
            'status' => 'nullable|in:active,inactive',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];

        if ($forCreate) {
            $rules['clinic_family_id'] = 'nullable|exists:clinic_families,id';
            $rules['family_name'] = 'nullable|string|max:255';
        } else {
            $rules['clinic_family_id'] = 'required|exists:clinic_families,id';
        }

        return $rules;
    }

    protected function normalizeAllergies(Request $request): ?array
    {
        if (! $request->has('allergies') || ! is_array($request->input('allergies'))) {
            return null;
        }

        $items = array_values(array_filter(array_map('trim', $request->input('allergies', []))));

        return empty($items) ? null : $items;
    }

    protected function normalizeEmergencyContacts(Request $request): ?array
    {
        if (! $request->has('emergency_contacts') || ! is_array($request->input('emergency_contacts'))) {
            return null;
        }

        $contacts = [];

        foreach ($request->input('emergency_contacts', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim($row['name'] ?? '');
            $phone = trim($row['phone'] ?? '');
            $relationship = trim($row['relationship'] ?? '');

            if ($name === '' && $phone === '' && $relationship === '') {
                continue;
            }

            $contacts[] = [
                'name' => $name ?: null,
                'phone' => $phone ?: null,
                'relationship' => $relationship ?: null,
            ];
        }

        return empty($contacts) ? null : $contacts;
    }

    protected function buildInsuranceInfo(array $validated): ?array
    {
        if (empty($validated['insurance_provider']) && empty($validated['insurance_policy_number'])) {
            return null;
        }

        return [
            'provider' => $validated['insurance_provider'] ?? null,
            'policy_number' => $validated['insurance_policy_number'] ?? null,
        ];
    }
}
