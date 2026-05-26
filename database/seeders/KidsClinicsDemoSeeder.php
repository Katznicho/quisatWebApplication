<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\ClinicFamily;
use App\Models\ClinicFamilyMember;
use App\Models\ClinicPatient;
use App\Models\Feature;
use App\Models\ParentGuardian;
use App\Models\Student;
use Illuminate\Database\Seeder;

class KidsClinicsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $feature = Feature::where('name', 'Kids Clinics')->first();

        if (! $feature) {
            $this->command->error('Kids Clinics feature not found. Run: php artisan db:seed --class=KidsClinicsFeatureSeeder');

            return;
        }

        $business = Business::where('id', '!=', 1)->first();

        if (! $business) {
            $this->command->error('No business found to seed demo clinic data.');

            return;
        }

        $enabled = collect($business->enabled_feature_ids ?? [])->map(fn ($id) => (int) $id);

        if (! $enabled->contains((int) $feature->id)) {
            $ids = $enabled->push((int) $feature->id)->unique()->values()->all();
            $business->update(['enabled_feature_ids' => $ids]);
            $this->command->info("Enabled Kids Clinics on business: {$business->name}");
        }

        $parent = ParentGuardian::where('business_id', $business->id)->first();

        $family = ClinicFamily::firstOrCreate(
            [
                'business_id' => $business->id,
                'family_name' => 'Demo Family',
            ],
            [
                'primary_parent_guardian_id' => $parent?->id,
                'notes' => 'Sample family for Kids Clinics module testing.',
                'status' => 'active',
            ]
        );

        if ($parent && ! $family->members()->where('parent_guardian_id', $parent->id)->exists()) {
            ClinicFamilyMember::create([
                'clinic_family_id' => $family->id,
                'parent_guardian_id' => $parent->id,
                'relationship' => 'guardian',
                'is_primary' => true,
            ]);
        }

        $patients = [
            ['first_name' => 'Amina', 'last_name' => 'Okello', 'gender' => 'female', 'date_of_birth' => '2018-03-15'],
            ['first_name' => 'Brian', 'last_name' => 'Okello', 'gender' => 'male', 'date_of_birth' => '2020-07-22'],
        ];

        foreach ($patients as $data) {
            ClinicPatient::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'clinic_family_id' => $family->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                ],
                array_merge($data, [
                    'parent_guardian_id' => $parent?->id,
                    'blood_group' => 'O+',
                    'allergies' => ['Peanuts'],
                    'emergency_contacts' => [
                        [
                            'name' => 'Grace Okello',
                            'phone' => '+256700000001',
                            'relationship' => 'Mother',
                        ],
                    ],
                    'status' => 'active',
                ])
            );
        }

        $studentCount = 0;
        Student::where('business_id', $business->id)->each(function (Student $student) use (&$studentCount) {
            $student->ensureAccessCode();
            $studentCount++;
        });

        $this->command->info("Demo clinic data ready for: {$business->name}");
        $this->command->info("Family access code: {$family->access_code}");
        $this->command->info("Ensured CHD access codes for {$studentCount} student(s) at this business.");
    }
}
