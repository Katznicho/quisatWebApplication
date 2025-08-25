<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Currency;
use Carbon\Carbon;

class CompleteSchoolDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create default currency
        $currency = Currency::firstOrCreate(
            ['code' => 'UGX'],
            [
                'name' => 'Ugandan Shilling',
                'symbol' => 'UGX',
                'rate' => 1.0,
                'is_default' => true,
                'status' => 'active',
                'position' => 1,
            ]
        );

        // Create School Management Features
        $schoolFeatures = [
            // Core Academic Features
            [
                'name' => 'Subject Management',
                'description' => 'Manage academic subjects, curriculum, and course materials',
                'price' => 50.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Class Room Management',
                'description' => 'Manage physical classrooms, capacity, and room assignments',
                'price' => 40.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Timetable Management',
                'description' => 'Create and manage class schedules, teacher assignments',
                'price' => 60.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Exam Management',
                'description' => 'Create exams, schedule tests, and manage examination periods',
                'price' => 70.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Grade Management',
                'description' => 'Record and track student grades, performance analytics',
                'price' => 80.00,
                'currency_id' => $currency->id,
            ],
            
            // Student Lifecycle Features
            [
                'name' => 'Student Management',
                'description' => 'Complete student records, enrollment, and academic tracking',
                'price' => 100.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Parent Guardian Management',
                'description' => 'Manage parent/guardian information and relationships',
                'price' => 45.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Attendance Management',
                'description' => 'Track daily attendance, absences, and attendance reports',
                'price' => 55.00,
                'currency_id' => $currency->id,
            ],
            
            // Financial Features
            [
                'name' => 'Fee Management',
                'description' => 'Manage tuition fees, payments, and financial records',
                'price' => 90.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Payment Processing',
                'description' => 'Process payments, generate receipts, and track balances',
                'price' => 75.00,
                'currency_id' => $currency->id,
            ],
            
            // Administrative Features
            [
                'name' => 'Multi-Branch Management',
                'description' => 'Manage multiple school locations and branches',
                'price' => 120.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Role-Based Access Control',
                'description' => 'Manage user roles, permissions, and access levels',
                'price' => 65.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Staff Management',
                'description' => 'Manage teachers, staff, and administrative personnel',
                'price' => 85.00,
                'currency_id' => $currency->id,
            ],
            
            // Advanced Features
            [
                'name' => 'Report Generation',
                'description' => 'Generate academic reports, financial reports, and analytics',
                'price' => 95.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Communication System',
                'description' => 'Send notifications, messages, and announcements',
                'price' => 60.00,
                'currency_id' => $currency->id,
            ],
            [
                'name' => 'Document Management',
                'description' => 'Store and manage certificates, reports, and school documents',
                'price' => 70.00,
                'currency_id' => $currency->id,
            ],
        ];

        foreach ($schoolFeatures as $featureData) {
            Feature::firstOrCreate(
                ['name' => $featureData['name']],
                $featureData
            );
        }

        $this->command->info('School Management Features created successfully!');

        // Get the first business (assuming it's a school)
        $business = Business::first();
        if (!$business) {
            $this->command->error('No business found. Please run AdminAndStaffSeeder first.');
            return;
        }

        // Get or create branch
        $branch = Branch::firstOrCreate(
            ['name' => 'Main Campus'],
            [
                'code' => 'MC001',
                'address' => '123 Main Street, Kampala',
                'phone' => '+256-123-456-789',
                'email' => 'main@school.com',
                'business_id' => $business->id,
                'status' => 'active',
            ]
        );

        // Create subjects
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH'],
            ['name' => 'English Language', 'code' => 'ENG'],
            ['name' => 'Science', 'code' => 'SCI'],
            ['name' => 'Social Studies', 'code' => 'SOC'],
            ['name' => 'Physical Education', 'code' => 'PE'],
            ['name' => 'Art and Craft', 'code' => 'ART'],
            ['name' => 'Religious Education', 'code' => 'RE'],
            ['name' => 'Computer Studies', 'code' => 'COMP'],
        ];

        foreach ($subjects as $subjectData) {
            Subject::firstOrCreate(
                ['code' => $subjectData['code']],
                [
                    'name' => $subjectData['name'],
                    'description' => $subjectData['name'] . ' for primary school students',
                    'business_id' => $business->id,
                    'status' => 'active',
                ]
            );
        }

        // Create class rooms
        $classRooms = [
            ['name' => 'Primary 1', 'code' => 'P1', 'capacity' => 30],
            ['name' => 'Primary 2', 'code' => 'P2', 'capacity' => 30],
            ['name' => 'Primary 3', 'code' => 'P3', 'capacity' => 30],
            ['name' => 'Primary 4', 'code' => 'P4', 'capacity' => 30],
            ['name' => 'Primary 5', 'code' => 'P5', 'capacity' => 30],
            ['name' => 'Primary 6', 'code' => 'P6', 'capacity' => 30],
            ['name' => 'Primary 7', 'code' => 'P7', 'capacity' => 30],
        ];

        foreach ($classRooms as $classData) {
            ClassRoom::firstOrCreate(
                ['code' => $classData['code']],
                [
                    'name' => $classData['name'],
                    'description' => $classData['name'] . ' classroom',
                    'capacity' => $classData['capacity'],
                    'business_id' => $business->id,
                    'branch_id' => $branch->id,
                    'status' => 'active',
                ]
            );
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
            $teacher = User::firstOrCreate(
                ['email' => $teacherData['email']],
                [
                    'name' => $teacherData['name'],
                    'phone' => $teacherData['phone'],
                    'password' => bcrypt('password123'),
                    'business_id' => $business->id,
                    'branch_id' => $branch->id,
                    'role_id' => $staffRole->id,
                    'status' => 'active',
                ]
            );
            $teacherIds[] = $teacher->id;
        }

        // Create parent guardians
        $parentGuardians = [
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@email.com',
                'phone' => '+1234567890',
                'relationship' => 'father',
            ],
            [
                'first_name' => 'Mary',
                'last_name' => 'Johnson',
                'email' => 'mary.johnson@email.com',
                'phone' => '+1234567891',
                'relationship' => 'mother',
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Brown',
                'email' => 'david.brown@email.com',
                'phone' => '+1234567892',
                'relationship' => 'father',
            ],
        ];

        foreach ($parentGuardians as $parentData) {
            ParentGuardian::firstOrCreate(
                ['email' => $parentData['email']],
                [
                    'first_name' => $parentData['first_name'],
                    'last_name' => $parentData['last_name'],
                    'phone' => $parentData['phone'],
                    'relationship' => $parentData['relationship'],
                    'business_id' => $business->id,
                    'status' => 'active',
                ]
            );
        }

        // Create students
        $students = [
            [
                'first_name' => 'Emma',
                'last_name' => 'Smith',
                'email' => 'emma.smith@student.com',
                'date_of_birth' => '2015-03-15',
                'gender' => 'female',
                'student_id' => 'STU001',
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Johnson',
                'email' => 'james.johnson@student.com',
                'date_of_birth' => '2014-07-22',
                'gender' => 'male',
                'student_id' => 'STU002',
            ],
            [
                'first_name' => 'Sophia',
                'last_name' => 'Brown',
                'email' => 'sophia.brown@student.com',
                'date_of_birth' => '2015-11-08',
                'gender' => 'female',
                'student_id' => 'STU003',
            ],
        ];

        $classRoom = ClassRoom::first();
        $parentGuardian = ParentGuardian::first();

        foreach ($students as $studentData) {
            Student::firstOrCreate(
                ['student_id' => $studentData['student_id']],
                [
                    'first_name' => $studentData['first_name'],
                    'last_name' => $studentData['last_name'],
                    'email' => $studentData['email'],
                    'date_of_birth' => $studentData['date_of_birth'],
                    'gender' => $studentData['gender'],
                    'admission_date' => Carbon::now()->subMonths(6),
                    'business_id' => $business->id,
                    'branch_id' => $branch->id,
                    'class_room_id' => $classRoom->id,
                    'parent_guardian_id' => $parentGuardian->id,
                    'status' => 'active',
                ]
            );
        }

        // Create timetables
        $subjects = Subject::take(4)->get();
        $classRooms = ClassRoom::take(3)->get();

        foreach ($classRooms as $index => $classRoom) {
            foreach ($subjects as $subjectIndex => $subject) {
                Timetable::firstOrCreate(
                    [
                        'class_room_id' => $classRoom->id,
                        'subject_id' => $subject->id,
                        'day_of_week' => $subjectIndex + 1,
                    ],
                    [
                        'business_id' => $business->id,
                        'branch_id' => $branch->id,
                        'teacher_id' => $teacherIds[$index % count($teacherIds)],
                        'start_time' => '08:00',
                        'end_time' => '09:00',
                        'room_number' => 'Room ' . ($index + 1),
                        'status' => 'active',
                    ]
                );
            }
        }

        // Create exams
        $examTypes = ['midterm', 'final', 'quiz'];
        $subjects = Subject::take(3)->get();

        foreach ($subjects as $subject) {
            foreach ($examTypes as $examType) {
                Exam::firstOrCreate(
                    [
                        'name' => $examType . ' ' . $subject->name,
                        'subject_id' => $subject->id,
                        'class_room_id' => $classRoom->id,
                    ],
                    [
                        'description' => $examType . ' examination for ' . $subject->name,
                        'business_id' => $business->id,
                        'exam_date' => Carbon::now()->addDays(rand(7, 30)),
                        'start_time' => '09:00',
                        'end_time' => '11:00',
                        'total_marks' => 100,
                        'passing_marks' => 50,
                        'exam_type' => $examType,
                        'status' => 'scheduled',
                    ]
                );
            }
        }

        // Create attendance records
        $students = Student::all();
        $attendanceStatuses = ['present', 'absent', 'late', 'excused'];

        foreach ($students as $student) {
            for ($i = 0; $i < 5; $i++) {
                Attendance::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'class_room_id' => $student->class_room_id,
                        'attendance_date' => Carbon::now()->subDays($i),
                    ],
                    [
                        'business_id' => $business->id,
                        'status' => $attendanceStatuses[array_rand($attendanceStatuses)],
                        'marked_by' => $teacherIds[array_rand($teacherIds)],
                    ]
                );
            }
        }

        // Create fees
        $feeTypes = ['tuition', 'library', 'transport', 'lunch'];
        $students = Student::all();

        foreach ($students as $student) {
            foreach ($feeTypes as $feeType) {
                Fee::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'fee_type' => $feeType,
                    ],
                    [
                        'business_id' => $business->id,
                        'amount' => rand(50000, 200000),
                        'amount_paid' => rand(0, 100000),
                        'balance' => rand(0, 150000),
                        'due_date' => Carbon::now()->addDays(rand(1, 30)),
                        'payment_status' => 'pending',
                    ]
                );
            }
        }

        $this->command->info('Complete school data seeded successfully!');
    }
}
