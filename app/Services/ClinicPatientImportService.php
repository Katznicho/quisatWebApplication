<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ClinicFamily;
use App\Models\ClinicFamilyMember;
use App\Models\ClinicPatient;
use App\Models\Student;

class ClinicPatientImportService
{
    public function attachStudentToClinic(Student $student, Business $clinic): ClinicPatient
    {
        if (! $clinic->hasFeatureByName('Kids Clinics')) {
            throw new \InvalidArgumentException('This business is not a Kids Clinic.');
        }

        $student->ensureAccessCode();

        $existing = ClinicPatient::where('business_id', $clinic->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $family = ClinicFamily::firstOrCreate(
            [
                'business_id' => $clinic->id,
                'primary_parent_guardian_id' => $student->parent_guardian_id,
            ],
            [
                'family_name' => trim(($student->parentGuardian?->first_name ?? '').' '.($student->parentGuardian?->last_name ?? '')).' Family',
                'status' => 'active',
                'access_code' => ClinicFamily::generateUniqueAccessCode($clinic->id),
            ]
        );

        if ($student->parent_guardian_id) {
            ClinicFamilyMember::firstOrCreate(
                [
                    'clinic_family_id' => $family->id,
                    'parent_guardian_id' => $student->parent_guardian_id,
                ],
                [
                    'relationship' => $student->parentGuardian?->relationship ?? 'guardian',
                    'is_primary' => true,
                ]
            );
        }

        return ClinicPatient::create([
            'business_id' => $clinic->id,
            'clinic_family_id' => $family->id,
            'parent_guardian_id' => $student->parent_guardian_id,
            'student_id' => $student->id,
            'school_access_code' => $student->access_code,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'date_of_birth' => $student->date_of_birth,
            'gender' => $student->gender,
            'photo' => $student->photo,
            'status' => 'active',
        ]);
    }
}
