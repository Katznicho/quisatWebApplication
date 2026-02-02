<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Role;
use Carbon\Carbon;

class SchoolDataSeeder extends Seeder
{
    /**
     * 
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first business (assuming it's a school)
        $business = Business::first();
        if (!$business) {
            $this->command->error('No business found. Please run AdminAndStaffSeeder first.');
            return;
        }

        // Get or create branch
        $branch = Branch::firstOrCreate([
            'name' => 'Main Campus',
            'business_id' => $business->id,
        ], [
            'code' => 'MC001',
            'address' => '123 School Street, City',
            'phone' => '+1234567890',
            'email' => 'main@school.com',
            'status' => 'active',
        ]);

        // Create subjects
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH001', 'description' => 'Basic mathematics for primary school'],
            ['name' => 'English Language', 'code' => 'ENG001', 'description' => 'English language and literature'],
            ['name' => 'Science', 'code' => 'SCI001', 'description' => 'General science for primary school'],
            ['name' => 'Social Studies', 'code' => 'SOC001', 'description' => 'History, geography, and civics'],
            ['name' => 'Physical Education', 'code' => 'PE001', 'description' => 'Physical education and sports'],
            ['name' => 'Art and Craft', 'code' => 'ART001', 'description' => 'Creative arts and crafts'],
            ['name' => 'Music', 'code' => 'MUS001', 'description' => 'Music and singing'],
            ['name' => 'Computer Studies', 'code' => 'COMP001', 'description' => 'Basic computer skills'],
        ];

        foreach ($subjects as $subjectData) {
            Subject::firstOrCreate([
                'code' => $subjectData['code'],
                'business_id' => $business->id,
            ], [
                'name' => $subjectData['name'],
                'description' => $subjectData['description'],
                'status' => 'active',
            ]);
        }

        // Create class rooms
        $classRooms = [
            ['name' => 'Class 1A', 'code' => 'C1A', 'capacity' => 25],
            ['name' => 'Class 1B', 'code' => 'C1B', 'capacity' => 25],
            ['name' => 'Class 2A', 'code' => 'C2A', 'capacity' => 25],
            ['name' => 'Class 2B', 'code' => 'C2B', 'capacity' => 25],
            ['name' => 'Class 3A', 'code' => 'C3A', 'capacity' => 25],
            ['name' => 'Class 3B', 'code' => 'C3B', 'capacity' => 25],
            ['name' => 'Class 4A', 'code' => 'C4A', 'capacity' => 25],
            ['name' => 'Class 4B', 'code' => 'C4B', 'capacity' => 25],
            ['name' => 'Class 5A', 'code' => 'C5A', 'capacity' => 25],
            ['name' => 'Class 5B', 'code' => 'C5B', 'capacity' => 25],
            ['name' => 'Class 6A', 'code' => 'C6A', 'capacity' => 25],
            ['name' => 'Class 6B', 'code' => 'C6B', 'capacity' => 25],
        ];

        foreach ($classRooms as $classData) {
            ClassRoom::firstOrCreate([
                'code' => $classData['code'],
                'business_id' => $business->id,
            ], [
                'name' => $classData['name'],
                'capacity' => $classData['capacity'],
                'branch_id' => $branch->id,
                'status' => 'active',
            ]);
        }

        // Create parent guardians
        $parentGuardians = [
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@email.com',
                'phone' => '+1234567891',
                'relationship' => 'father',
                'occupation' => 'Engineer',
            ],
            [
                'first_name' => 'Mary',
                'last_name' => 'Johnson',
                'email' => 'mary.johnson@email.com',
                'phone' => '+1234567892',
                'relationship' => 'mother',
                'occupation' => 'Teacher',
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Williams',
                'email' => 'david.williams@email.com',
                'phone' => '+1234567893',
                'relationship' => 'father',
                'occupation' => 'Doctor',
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Brown',
                'email' => 'sarah.brown@email.com',
                'phone' => '+1234567894',
                'relationship' => 'mother',
                'occupation' => 'Nurse',
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Davis',
                'email' => 'michael.davis@email.com',
                'phone' => '+1234567895',
                'relationship' => 'father',
                'occupation' => 'Lawyer',
            ],
        ];

        foreach ($parentGuardians as $parentData) {
            ParentGuardian::firstOrCreate([
                'email' => $parentData['email'],
                'business_id' => $business->id,
            ], [
                'first_name' => $parentData['first_name'],
                'last_name' => $parentData['last_name'],
                'phone' => $parentData['phone'],
                'relationship' => $parentData['relationship'],
                'occupation' => $parentData['occupation'],
                'status' => 'active',
            ]);
        }

        // Create students
        $students = [
            [
                'first_name' => 'Emma',
                'last_name' => 'Smith',
                'email' => 'emma.smith@student.com',
                'date_of_birth' => '2018-03-15',
                'gender' => 'female',
                'student_id' => 'STU001',
                'admission_date' => '2023-09-01',
                'parent_guardian_id' => 1,
                'class_room_id' => 1,
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Johnson',
                'email' => 'james.johnson@student.com',
                'date_of_birth' => '2018-07-22',
                'gender' => 'male',
                'student_id' => 'STU002',
                'admission_date' => '2023-09-01',
                'parent_guardian_id' => 2,
                'class_room_id' => 1,
            ],
            [
                'first_name' => 'Sophia',
                'last_name' => 'Williams',
                'email' => 'sophia.williams@student.com',
                'date_of_birth' => '2017-11-08',
                'gender' => 'female',
                'student_id' => 'STU003',
                'admission_date' => '2023-09-01',
                'parent_guardian_id' => 3,
                'class_room_id' => 3,
            ],
            [
                'first_name' => 'Liam',
                'last_name' => 'Brown',
                'email' => 'liam.brown@student.com',
                'date_of_birth' => '2017-05-14',
                'gender' => 'male',
                'student_id' => 'STU004',
                'admission_date' => '2023-09-01',
                'parent_guardian_id' => 4,
                'class_room_id' => 3,
            ],
            [
                'first_name' => 'Olivia',
                'last_name' => 'Davis',
                'email' => 'olivia.davis@student.com',
                'date_of_birth' => '2016-09-30',
                'gender' => 'female',
                'student_id' => 'STU005',
                'admission_date' => '2023-09-01',
                'parent_guardian_id' => 5,
                'class_room_id' => 5,
            ],
        ];

        foreach ($students as $studentData) {
            Student::firstOrCreate([
                'email' => $studentData['email'],
                'business_id' => $business->id,
            ], [
                'first_name' => $studentData['first_name'],
                'last_name' => $studentData['last_name'],
                'date_of_birth' => $studentData['date_of_birth'],
                'gender' => $studentData['gender'],
                'student_id' => $studentData['student_id'],
                'admission_date' => $studentData['admission_date'],
                'parent_guardian_id' => $studentData['parent_guardian_id'],
                'class_room_id' => $studentData['class_room_id'],
                'branch_id' => $branch->id,
                'status' => 'active',
            ]);
        }

        // Create teachers (staff users)
        $teachers = [
            [
                'name' => 'Mrs. Jennifer Wilson',
                'email' => 'jennifer.wilson@school.com',
                'phone' => '+1234567896',
            ],
            [
                'name' => 'Mr. Robert Taylor',
                'email' => 'robert.taylor@school.com',
                'phone' => '+1234567897',
            ],
            [
                'name' => 'Ms. Lisa Anderson',
                'email' => 'lisa.anderson@school.com',
                'phone' => '+1234567898',
            ],
        ];

        $staffRole = Role::where('name', 'Staff')->first();
        $teacherIds = [];

        foreach ($teachers as $teacherData) {
            $teacher = User::firstOrCreate([
                'email' => $teacherData['email'],
                'business_id' => $business->id,
            ], [
                'name' => $teacherData['name'],
                'phone' => $teacherData['phone'],
                'password' => bcrypt('password123'),
                'role_id' => $staffRole->id,
                'branch_id' => $branch->id,
                'status' => 'active',
            ]);
            $teacherIds[] = $teacher->id;
        }

        // Create timetables
        $timetables = [
            // Monday
            ['day_of_week' => 'monday', 'start_time' => '08:00', 'end_time' => '09:00', 'subject_id' => 1, 'teacher_id' => $teacherIds[0], 'class_room_id' => 1],
            ['day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '10:00', 'subject_id' => 2, 'teacher_id' => $teacherIds[1], 'class_room_id' => 1],
            ['day_of_week' => 'monday', 'start_time' => '10:30', 'end_time' => '11:30', 'subject_id' => 3, 'teacher_id' => $teacherIds[2], 'class_room_id' => 1],
            ['day_of_week' => 'monday', 'start_time' => '11:30', 'end_time' => '12:30', 'subject_id' => 4, 'teacher_id' => $teacherIds[0], 'class_room_id' => 1],
            
            // Tuesday
            ['day_of_week' => 'tuesday', 'start_time' => '08:00', 'end_time' => '09:00', 'subject_id' => 2, 'teacher_id' => $teacherIds[1], 'class_room_id' => 1],
            ['day_of_week' => 'tuesday', 'start_time' => '09:00', 'end_time' => '10:00', 'subject_id' => 1, 'teacher_id' => $teacherIds[0], 'class_room_id' => 1],
            ['day_of_week' => 'tuesday', 'start_time' => '10:30', 'end_time' => '11:30', 'subject_id' => 5, 'teacher_id' => $teacherIds[2], 'class_room_id' => 1],
            ['day_of_week' => 'tuesday', 'start_time' => '11:30', 'end_time' => '12:30', 'subject_id' => 6, 'teacher_id' => $teacherIds[1], 'class_room_id' => 1],
            
            // Wednesday
            ['day_of_week' => 'wednesday', 'start_time' => '08:00', 'end_time' => '09:00', 'subject_id' => 3, 'teacher_id' => $teacherIds[2], 'class_room_id' => 1],
            ['day_of_week' => 'wednesday', 'start_time' => '09:00', 'end_time' => '10:00', 'subject_id' => 4, 'teacher_id' => $teacherIds[0], 'class_room_id' => 1],
            ['day_of_week' => 'wednesday', 'start_time' => '10:30', 'end_time' => '11:30', 'subject_id' => 1, 'teacher_id' => $teacherIds[0], 'class_room_id' => 1],
            ['day_of_week' => 'wednesday', 'start_time' => '11:30', 'end_time' => '12:30', 'subject_id' => 7, 'teacher_id' => $teacherIds[1], 'class_room_id' => 1],
            
            // Thursday
            ['day_of_week' => 'thursday', 'start_time' => '08:00', 'end_time' => '09:00', 'subject_id' => 2, 'teacher_id' => $teacherIds[1], 'class_room_id' => 1],
            ['day_of_week' => 'thursday', 'start_time' => '09:00', 'end_time' => '10:00', 'subject_id' => 8, 'teacher_id' => $teacherIds[2], 'class_room_id' => 1],
            ['day_of_week' => 'thursday', 'start_time' => '10:30', 'end_time' => '11:30', 'subject_id' => 3, 'teacher_id' => $teacherIds[2], 'class_room_id' => 1],
            ['day_of_week' => 'thursday', 'start_time' => '11:30', 'end_time' => '12:30', 'subject_id' => 5, 'teacher_id' => $teacherIds[2], 'class_room_id' => 1],
            
            // Friday
            ['day_of_week' => 'friday', 'start_time' => '08:00', 'end_time' => '09:00', 'subject_id' => 1, 'teacher_id' => $teacherIds[0], 'class_room_id' => 1],
            ['day_of_week' => 'friday', 'start_time' => '09:00', 'end_time' => '10:00', 'subject_id' => 4, 'teacher_id' => $teacherIds[0], 'class_room_id' => 1],
            ['day_of_week' => 'friday', 'start_time' => '10:30', 'end_time' => '11:30', 'subject_id' => 2, 'teacher_id' => $teacherIds[1], 'class_room_id' => 1],
            ['day_of_week' => 'friday', 'start_time' => '11:30', 'end_time' => '12:30', 'subject_id' => 6, 'teacher_id' => $teacherIds[1], 'class_room_id' => 1],
        ];

        foreach ($timetables as $timetableData) {
            Timetable::firstOrCreate([
                'business_id' => $business->id,
                'class_room_id' => $timetableData['class_room_id'],
                'subject_id' => $timetableData['subject_id'],
                'teacher_id' => $timetableData['teacher_id'],
                'day_of_week' => $timetableData['day_of_week'],
                'start_time' => $timetableData['start_time'],
                'end_time' => $timetableData['end_time'],
            ], [
                'branch_id' => $branch->id,
                'status' => 'active',
            ]);
        }

        $this->command->info('School data seeded successfully!');
        $this->command->info('Created:');
        $this->command->info('- ' . Subject::count() . ' subjects');
        $this->command->info('- ' . ClassRoom::count() . ' class rooms');
        $this->command->info('- ' . ParentGuardian::count() . ' parent guardians');
        $this->command->info('- ' . Student::count() . ' students');
        $this->command->info('- ' . Timetable::count() . ' timetable entries');
    }
}
