<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ClinicAppointment;
use App\Models\ClinicPatient;
use App\Models\Feature;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Services\ClinicPatientImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClinicController extends Controller
{
    public function __construct(
        protected ClinicPatientImportService $importService
    ) {}

    /**
     * List businesses that offer Kids Clinics (for parents browsing in the app).
     */
    public function index(Request $request)
    {
        try {
            $feature = Feature::where('name', 'Kids Clinics')->first();

            if (! $feature) {
                return response()->json([
                    'success' => true,
                    'data' => ['clinics' => []],
                ]);
            }

            $search = trim((string) $request->query('search', ''));

            $clinics = Business::query()
                ->where('id', '!=', 1)
                ->where('status', 'active')
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->get()
                ->filter(fn (Business $b) => $b->hasFeatureByName('Kids Clinics'))
                ->map(fn (Business $b) => $this->transformClinic($b))
                ->values();

            return response()->json([
                'success' => true,
                'data' => ['clinics' => $clinics],
            ]);
        } catch (\Exception $e) {
            Log::error('ClinicController::index - '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to load clinics.',
            ], 500);
        }
    }

    /**
     * Clinic detail + parent's linked children at this clinic (if authenticated parent).
     */
    public function show(Request $request, $id)
    {
        try {
            $clinic = $this->findClinicBusiness($id);

            if (! $clinic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clinic not found.',
                ], 404);
            }

            $linkedChildren = [];
            $user = $request->user();

            if ($user instanceof ParentGuardian) {
                $linkedChildren = ClinicPatient::where('business_id', $clinic->id)
                    ->where('parent_guardian_id', $user->id)
                    ->with('student:id,first_name,last_name,access_code')
                    ->get()
                    ->map(fn (ClinicPatient $p) => [
                        'clinic_patient_id' => $p->id,
                        'student_id' => $p->student_id,
                        'full_name' => $p->full_name,
                        'school_access_code' => $p->school_access_code,
                        'patient_number' => $p->patient_number,
                        'status' => $p->status,
                        'linked_at' => optional($p->created_at)->toIso8601String(),
                    ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'clinic' => $this->transformClinic($clinic, true),
                    'linked_children' => $linkedChildren,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ClinicController::show - '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to load clinic.',
            ], 500);
        }
    }

    /**
     * Parent: full clinic hub — linked children, upcoming appointments, child codes.
     */
    public function overview(Request $request, $id)
    {
        $user = $request->user();

        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can view clinic details.',
            ], 403);
        }

        $clinic = $this->findClinicBusiness($id);

        if (! $clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found.',
            ], 404);
        }

        $patients = ClinicPatient::where('business_id', $clinic->id)
            ->where('parent_guardian_id', $user->id)
            ->with([
                'student:id,first_name,last_name,access_code',
                'appointments' => fn ($q) => $q->orderBy('scheduled_at'),
            ])
            ->get();

        $linkedChildren = $patients->map(function (ClinicPatient $p) {
            $upcoming = $p->appointments
                ->filter(fn (ClinicAppointment $a) => $a->status === 'scheduled' && $a->scheduled_at->isFuture())
                ->values()
                ->map(fn (ClinicAppointment $a) => $this->transformAppointment($a));

            $past = $p->appointments
                ->filter(fn (ClinicAppointment $a) => $a->status !== 'scheduled' || $a->scheduled_at->isPast())
                ->sortByDesc('scheduled_at')
                ->take(5)
                ->values()
                ->map(fn (ClinicAppointment $a) => $this->transformAppointment($a));

            return [
                'clinic_patient_id' => $p->id,
                'student_id' => $p->student_id,
                'full_name' => $p->full_name,
                'school_access_code' => $p->school_access_code,
                'patient_number' => $p->patient_number,
                'status' => $p->status,
                'linked_at' => optional($p->created_at)->toIso8601String(),
                'upcoming_appointments' => $upcoming,
                'recent_appointments' => $past,
            ];
        });

        $children = $user->students()->get()->map(function (Student $student) {
            $code = $student->ensureAccessCode();

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'class' => $student->classRoom?->name,
                'access_code' => $code,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'clinic' => $this->transformClinic($clinic, true),
                'linked_children' => $linkedChildren,
                'children' => $children,
            ],
        ]);
    }

    /**
     * Parent: link a school child to this clinic using the child's access code.
     */
    public function attach(Request $request, $id)
    {
        $user = $request->user();

        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can link children to a clinic.',
            ], 403);
        }

        $validated = $request->validate([
            'child_access_code' => 'required|string|max:20',
        ]);

        $clinic = $this->findClinicBusiness($id);

        if (! $clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found.',
            ], 404);
        }

        $code = strtoupper(trim($validated['child_access_code']));

        $student = Student::where('access_code', $code)
            ->where('parent_guardian_id', $user->id)
            ->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid access code. Use the code shown for your child in the school app.',
            ], 422);
        }

        try {
            $patient = $this->importService->attachStudentToClinic($student, $clinic);

            return response()->json([
                'success' => true,
                'message' => $patient->wasRecentlyCreated
                    ? 'Child linked to clinic successfully.'
                    : 'This child is already linked to this clinic.',
                'data' => [
                    'clinic_patient' => [
                        'id' => $patient->id,
                        'patient_number' => $patient->patient_number,
                        'full_name' => $patient->full_name,
                        'school_access_code' => $patient->school_access_code,
                    ],
                    'clinic' => $this->transformClinic($clinic),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ClinicController::attach - '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Could not link child to clinic. Please try again.',
            ], 500);
        }
    }

    /**
     * Parent: list clinics where at least one child is linked.
     */
    public function myLinks(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can view clinic links.',
            ], 403);
        }

        $patients = ClinicPatient::where('parent_guardian_id', $user->id)
            ->with(['business:id,name,city,address,phone,email,logo', 'student:id,first_name,last_name,access_code'])
            ->orderByDesc('created_at')
            ->get();

        $grouped = $patients->groupBy('business_id')->map(function ($group) {
            $clinic = $group->first()->business;

            return [
                'clinic' => $clinic ? $this->transformClinic($clinic) : null,
                'children' => $group->map(fn (ClinicPatient $p) => [
                    'clinic_patient_id' => $p->id,
                    'full_name' => $p->full_name,
                    'school_access_code' => $p->school_access_code,
                    'patient_number' => $p->patient_number,
                    'linked_at' => optional($p->created_at)->toIso8601String(),
                ])->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => ['linked_clinics' => $grouped],
        ]);
    }

    /**
     * Parent: ensure each child has an access code (shown in app).
     */
    public function childAccessCodes(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can view child access codes.',
            ], 403);
        }

        $children = $user->students()->get()->map(function (Student $student) {
            $code = $student->ensureAccessCode();

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'class' => $student->classRoom?->name,
                'access_code' => $code,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => ['children' => $children],
        ]);
    }

    protected function findClinicBusiness($id): ?Business
    {
        $business = Business::where('id', '!=', 1)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (! $business || ! $business->hasFeatureByName('Kids Clinics')) {
            return null;
        }

        return $business;
    }

    protected function transformClinic(Business $business, bool $detailed = false): array
    {
        $logo = $business->logo
            ? (str_starts_with($business->logo, 'http') ? $business->logo : asset('storage/'.$business->logo))
            : null;

        $data = [
            'id' => $business->id,
            'uuid' => $business->uuid,
            'name' => $business->name,
            'city' => $business->city,
            'address' => $business->address,
            'phone' => $business->phone,
            'email' => $business->email,
            'logo_url' => $logo,
        ];

        if ($detailed) {
            $data['website_link'] = $business->website_link;
            $data['social_media_handles'] = $business->social_media_handles;
        }

        return $data;
    }

    protected function transformAppointment(ClinicAppointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
            'doctor_name' => $appointment->doctor_name,
            'appointment_type' => $appointment->appointment_type,
            'status' => $appointment->status,
            'notes' => $appointment->notes,
        ];
    }
}
