<?php

namespace App\Http\Controllers;

use App\Models\ClinicAppointment;
use App\Models\ClinicAppointmentType;
use App\Models\ClinicDoctor;
use App\Models\ClinicFamily;
use App\Models\ClinicFamilyMember;
use App\Models\ClinicPatient;
use App\Models\ClinicPatientVisit;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Services\ClinicPatientImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClinicPatientController extends Controller
{
    public function __construct(
        protected ClinicPatientImportService $importService
    ) {}

    public function index(Request $request)
    {
        $business = Auth::user()->business;
        $businessId = $business->id ?? 0;

        $stats = [
            'patients' => ClinicPatient::where('business_id', $businessId)->count(),
            'doctors' => ClinicDoctor::where('business_id', $businessId)->count(),
            'consultations' => ClinicPatientVisit::where('business_id', $businessId)->count(),
            'scheduled_appointments' => ClinicAppointment::where('business_id', $businessId)
                ->where('status', 'scheduled')
                ->count(),
            'appointment_types' => ClinicAppointmentType::where('business_id', $businessId)->count(),
        ];

        return view('clinic-patients.index', compact('stats'));
    }

    public function create(Request $request)
    {
        $business = Auth::user()->business;
        $businessId = $business->id ?? 0;
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
                $existingPatient = ClinicPatient::where('business_id', $businessId)
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

        if ($request->input('entry_mode') === 'manual') {
            $businessId = $business->id;
            $validated = $request->validate(array_merge(
                $this->patientFormRules(forCreate: true),
                [
                    'guardian_first_name' => 'required|string|max:255',
                    'guardian_last_name' => 'required|string|max:255',
                    'guardian_email' => 'nullable|email|max:255|unique:parent_guardians,email',
                    'guardian_phone' => 'required|string|max:50',
                    'guardian_relationship' => 'required|in:father,mother,guardian,other',
                ]
            ));

            $parentGuardian = $this->createManualParentGuardian($validated, $businessId);
            $family = $this->resolveFamily($request, $businessId, $parentGuardian?->id);

            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('clinic-patients', 'public');
            }

            $patient = ClinicPatient::create([
                'business_id' => $businessId,
                'clinic_family_id' => $family->id,
                'parent_guardian_id' => $parentGuardian?->id,
                'student_id' => null,
                'school_access_code' => null,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'allergies' => $this->normalizeAllergies($request),
                'emergency_contacts' => $this->normalizeEmergencyContacts($request),
                'insurance_info' => $this->buildInsuranceInfo($validated),
                'status' => $validated['status'] ?? 'active',
                'photo' => $photoPath,
            ]);

            if ($parentGuardian) {
                $this->ensureFamilyMember(
                    $family,
                    (int) $parentGuardian->id,
                    (int) $family->primary_parent_guardian_id === (int) $parentGuardian->id
                );
            }

            return redirect()
                ->route('clinic-patients.show', $patient)
                ->with('success', 'Patient registered successfully.');
        }

        $validated = $request->validate(array_merge([
            'child_access_code' => 'required|string|max:20',
        ], $this->importClinicDetailsRules()));

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

            $photoPath = $patient->photo;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('clinic-patients', 'public');
            }

            $patient->update([
                'blood_group' => $validated['blood_group'] ?? $patient->blood_group,
                'allergies' => $this->normalizeAllergies($request),
                'emergency_contacts' => $this->normalizeEmergencyContacts($request),
                'insurance_info' => $this->buildInsuranceInfo($validated),
                'status' => $validated['status'] ?? $patient->status,
                'photo' => $photoPath,
            ]);

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
            'access_code' => ClinicFamily::generateUniqueAccessCode($businessId),
            'family_name' => $request->input('family_name') ?: trim(($request->input('last_name') ?: 'Clinic').' Family'),
            'primary_parent_guardian_id' => $parentId,
            'status' => 'active',
        ]);
    }

    protected function createManualParentGuardian(array $validated, int $businessId): ParentGuardian
    {
        $email = $validated['guardian_email'] ?? null;

        if (! $email) {
            $email = 'clinic-guardian-'.Str::uuid().'@placeholder.local';
        }

        return ParentGuardian::create([
            'first_name' => $validated['guardian_first_name'],
            'last_name' => $validated['guardian_last_name'],
            'email' => $email,
            'phone' => $validated['guardian_phone'],
            'relationship' => $validated['guardian_relationship'],
            'business_id' => $businessId,
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
            $rules['family_name'] = 'nullable|string|max:255';
        } else {
            $rules['clinic_family_id'] = 'required|exists:clinic_families,id';
        }

        return $rules;
    }

    protected function importClinicDetailsRules(): array
    {
        return [
            'blood_group' => 'nullable|string|max:10',
            'allergies' => 'nullable|array',
            'allergies.*' => 'nullable|string|max:255',
            'emergency_contacts' => 'nullable|array',
            'emergency_contacts.*.name' => 'nullable|string|max:255',
            'emergency_contacts.*.phone' => 'nullable|string|max:50',
            'emergency_contacts.*.relationship' => 'nullable|string|max:50',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
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
