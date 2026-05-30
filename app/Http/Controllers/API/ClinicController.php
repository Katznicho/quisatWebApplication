<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ClinicAppointment;
use App\Models\ClinicAppointmentType;
use App\Models\ClinicDoctor;
use App\Models\ClinicService;
use App\Models\ClinicPatient;
use App\Models\ClinicPatientDocument;
use App\Models\ClinicPatientGrowthRecord;
use App\Models\ClinicPatientVaccination;
use App\Models\ClinicPatientVisit;
use App\Models\Feature;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Services\ClinicPatientImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

        $doctorSchedules = ClinicAppointment::query()
            ->where('business_id', $clinic->id)
            ->where('status', 'scheduled')
            ->whereNotNull('doctor_name')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn (ClinicAppointment $appointment) => trim((string) $appointment->doctor_name))
            ->map(function ($appointments, string $doctorName) {
                $nextSlot = $appointments->first();

                return [
                    'doctor_name' => $doctorName,
                    'next_slot_at' => $nextSlot?->scheduled_at?->toIso8601String(),
                    'upcoming_slots_count' => $appointments->count(),
                ];
            })
            ->values()
            ->take(8);

        $doctorProfiles = ClinicDoctor::query()
            ->where('business_id', $clinic->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['name', 'specialization'])
            ->keyBy('name');

        $doctorSchedules = $doctorSchedules->map(function (array $schedule) use ($doctorProfiles) {
            $profile = $doctorProfiles->get($schedule['doctor_name']);
            $schedule['specialization'] = $profile?->specialization;

            return $schedule;
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'clinic' => $this->transformClinic($clinic, true),
                'linked_children' => $linkedChildren,
                'children' => $children,
                'doctor_schedules' => $doctorSchedules,
                'services' => $this->getClinicServices($clinic->id),
            ],
        ]);
    }

    /**
     * Parent: full linked patient profile at a specific clinic.
     */
    public function patientProfile(Request $request, $id, ClinicPatient $clinic_patient)
    {
        $user = $request->user();

        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can view patient details.',
            ], 403);
        }

        $clinic = $this->findClinicBusiness($id);

        if (! $clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found.',
            ], 404);
        }

        if ($clinic_patient->business_id !== $clinic->id || $clinic_patient->parent_guardian_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found.',
            ], 404);
        }

        $clinic_patient->load([
            'parentGuardian:id,first_name,last_name,phone,email,relationship',
            'student:id,first_name,last_name,access_code,student_id,class_room_id',
            'student.classRoom:id,name',
            'family.members.parentGuardian:id,first_name,last_name,phone,email,relationship',
            'appointments' => fn ($q) => $q->orderByDesc('scheduled_at'),
            'visits' => fn ($q) => $q->orderByDesc('visited_at'),
            'vaccinations' => fn ($q) => $q->orderByDesc('scheduled_date')->orderByDesc('administered_date'),
            'growthRecords' => fn ($q) => $q->orderByDesc('recorded_on'),
            'documents' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'clinic' => $this->transformClinic($clinic, true),
                'patient' => $this->transformPatientProfile($clinic_patient),
                'booking_options' => [
                    'doctors' => $this->getClinicDoctors($clinic->id),
                    'appointment_types' => $this->getClinicAppointmentTypesForBooking($clinic->id),
                ],
            ],
        ]);
    }

    /**
     * Parent: book a new appointment for linked child.
     */
    public function bookPatientAppointment(Request $request, $id, ClinicPatient $clinic_patient)
    {
        $user = $request->user();

        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can book clinic appointments.',
            ], 403);
        }

        $clinic = $this->findClinicBusiness($id);

        if (! $clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found.',
            ], 404);
        }

        if ($clinic_patient->business_id !== $clinic->id || $clinic_patient->parent_guardian_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found.',
            ], 404);
        }

        $validated = $request->validate([
            'doctor_name' => [
                'required',
                'string',
                'max:120',
                Rule::exists('clinic_doctors', 'name')->where(function ($query) use ($clinic) {
                    $query->where('business_id', $clinic->id)->where('status', 'active');
                }),
            ],
            'appointment_type' => [
                'required',
                'string',
                'max:120',
                Rule::exists('clinic_appointment_types', 'name')->where(function ($query) use ($clinic) {
                    $query->where('business_id', $clinic->id)->where('status', 'active');
                }),
            ],
            'scheduled_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ], [
            'doctor_name.exists' => 'Please choose a doctor from this clinic.',
            'appointment_type.exists' => 'Please choose a valid clinic service.',
        ]);

        $appointment = ClinicAppointment::create([
            'business_id' => $clinic->id,
            'clinic_patient_id' => $clinic_patient->id,
            'scheduled_at' => $validated['scheduled_at'],
            'doctor_name' => $validated['doctor_name'],
            'appointment_type' => $validated['appointment_type'],
            'status' => 'scheduled',
            'notes' => $validated['notes'] ?? null,
            'created_by' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appointment request booked successfully.',
            'data' => [
                'appointment' => $this->transformAppointment($appointment),
            ],
        ]);
    }

    /**
     * Parent: delete a document for a linked child record.
     */
    public function deletePatientDocument(
        Request $request,
        $id,
        ClinicPatient $clinic_patient,
        ClinicPatientDocument $document
    ) {
        $user = $request->user();

        if (! $user instanceof ParentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Only parents can delete clinic documents.',
            ], 403);
        }

        $clinic = $this->findClinicBusiness($id);

        if (! $clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found.',
            ], 404);
        }

        if ($clinic_patient->business_id !== $clinic->id || $clinic_patient->parent_guardian_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found.',
            ], 404);
        }

        if ($document->clinic_patient_id !== $clinic_patient->id || $document->business_id !== $clinic->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found for this child.',
            ], 404);
        }

        try {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('ClinicController::deletePatientDocument - '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Could not delete document right now.',
            ], 500);
        }
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
            'rating' => $business->rating !== null ? (float) $business->rating : null,
            'reviews_count' => $business->reviews_count !== null ? (int) $business->reviews_count : null,
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

    protected function transformPatientProfile(ClinicPatient $patient): array
    {
        $photoUrl = $patient->photo
            ? (str_starts_with($patient->photo, 'http') ? $patient->photo : asset('storage/'.$patient->photo))
            : null;

        return [
            'id' => $patient->id,
            'uuid' => $patient->uuid,
            'full_name' => $patient->full_name,
            'patient_number' => $patient->patient_number,
            'status' => $patient->status,
            'date_of_birth' => optional($patient->date_of_birth)->toDateString(),
            'age_years' => $patient->date_of_birth?->age,
            'gender' => $patient->gender,
            'blood_group' => $patient->blood_group,
            'school_access_code' => $patient->school_access_code,
            'photo_url' => $photoUrl,
            'allergies' => $patient->allergies ?? [],
            'emergency_contacts' => $patient->emergency_contacts ?? [],
            'insurance_info' => $patient->insurance_info,
            'parent_guardian' => $patient->parentGuardian ? [
                'id' => $patient->parentGuardian->id,
                'full_name' => $patient->parentGuardian->full_name,
                'phone' => $patient->parentGuardian->phone,
                'email' => $patient->parentGuardian->email,
                'relationship' => $patient->parentGuardian->relationship,
            ] : null,
            'linked_school_student' => $patient->student ? [
                'id' => $patient->student->id,
                'full_name' => trim($patient->student->first_name.' '.$patient->student->last_name),
                'student_id' => $patient->student->student_id,
                'class' => $patient->student->classRoom?->name,
                'access_code' => $patient->student->access_code,
            ] : null,
            'family_members' => $patient->family?->members?->map(function ($member) {
                return [
                    'id' => $member->id,
                    'full_name' => $member->parentGuardian?->full_name ?? 'Parent / Guardian',
                    'phone' => $member->parentGuardian?->phone,
                    'email' => $member->parentGuardian?->email,
                    'relationship' => $member->relationship,
                    'permissions' => $member->permissions ?? [],
                    'is_primary' => (bool) $member->is_primary,
                ];
            })->values() ?? [],
            'appointments' => $patient->appointments->map(fn (ClinicAppointment $appointment) => $this->transformAppointment($appointment))->values(),
            'visits' => $patient->visits->map(fn (ClinicPatientVisit $visit) => $this->transformVisit($visit))->values(),
            'vaccinations' => $patient->vaccinations->map(fn (ClinicPatientVaccination $vaccination) => $this->transformVaccination($vaccination))->values(),
            'growth_records' => $patient->growthRecords->map(fn (ClinicPatientGrowthRecord $record) => $this->transformGrowthRecord($record))->values(),
            'documents' => $patient->documents->map(fn (ClinicPatientDocument $document) => $this->transformPatientDocument($document))->values(),
        ];
    }

    protected function transformVisit(ClinicPatientVisit $visit): array
    {
        return [
            'id' => $visit->id,
            'visited_at' => $visit->visited_at->toIso8601String(),
            'doctor_name' => $visit->doctor_name,
            'visit_type' => $visit->visit_type,
            'status' => $visit->status,
            'chief_complaint' => $visit->chief_complaint,
            'consultation_notes' => $visit->consultation_notes,
            'treatment_plan' => $visit->treatment_plan,
            'prescriptions' => $visit->prescriptions,
            'lab_results' => $visit->lab_results,
            'follow_up_date' => optional($visit->follow_up_date)->toDateString(),
        ];
    }

    protected function transformVaccination(ClinicPatientVaccination $vaccination): array
    {
        return [
            'id' => $vaccination->id,
            'vaccine_name' => $vaccination->vaccine_name,
            'dose_label' => $vaccination->dose_label,
            'status' => $vaccination->status,
            'scheduled_date' => optional($vaccination->scheduled_date)->toDateString(),
            'administered_date' => optional($vaccination->administered_date)->toDateString(),
            'next_due_date' => optional($vaccination->next_due_date)->toDateString(),
            'batch_number' => $vaccination->batch_number,
            'notes' => $vaccination->notes,
        ];
    }

    protected function transformGrowthRecord(ClinicPatientGrowthRecord $record): array
    {
        return [
            'id' => $record->id,
            'recorded_on' => $record->recorded_on->toDateString(),
            'height_cm' => $record->height_cm ? (float) $record->height_cm : null,
            'weight_kg' => $record->weight_kg ? (float) $record->weight_kg : null,
            'head_circumference_cm' => $record->head_circumference_cm ? (float) $record->head_circumference_cm : null,
            'bmi' => $record->bmi,
            'notes' => $record->notes,
        ];
    }

    protected function transformPatientDocument(ClinicPatientDocument $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description,
            'type' => $document->type,
            'file_url' => $document->file_url,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'created_at' => $document->created_at?->toIso8601String(),
        ];
    }

    protected function getClinicDoctors(int $businessId)
    {
        return ClinicDoctor::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (ClinicDoctor $doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'specialization' => $doctor->specialization,
                ];
            })
            ->values();
    }

    protected function getClinicAppointmentTypesForBooking(int $businessId)
    {
        return ClinicAppointmentType::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->whereIn('applies_to', ['appointments', 'both'])
            ->orderBy('name')
            ->get()
            ->map(function (ClinicAppointmentType $type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                ];
            })
            ->values();
    }

    protected function getClinicServices(int $businessId)
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('clinic_services')) {
            return collect();
        }

        return ClinicService::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (ClinicService $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'duration_minutes' => $service->duration_minutes,
                    'price' => $service->price !== null ? (float) $service->price : null,
                ];
            })
            ->values();
    }
}
