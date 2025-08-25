<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\Term;
use App\Models\Business;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;

class TermManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first business
        $business = Business::find(3); // Tech Solutions Inc
        if (!$business) {
            $this->command->error('No business found. Please run AdminAndStaffSeeder first.');
            return;
        }

        // Get admin user
        $adminUser = User::where('business_id', $business->id)
            ->whereHas('role', function($query) {
                $query->where('name', 'Admin');
            })
            ->first();

        if (!$adminUser) {
            $this->command->error('No admin user found for business. Please run AdminAndStaffSeeder first.');
            return;
        }

        // Create Term Management feature
        $termFeature = Feature::firstOrCreate(
            ['name' => 'Term Management'],
            [
                'description' => 'Comprehensive academic term management system with term dates, grading periods, and financial settings',
                'price' => 85.00,
                'currency_id' => 1,
            ]
        );

        $this->command->info('Term Management feature created successfully.');

        // Create sample academic terms for 2024-2025
        $terms = [
            [
                'name' => 'First Term 2024-2025',
                'code' => 'T1-2024',
                'description' => 'First academic term of the 2024-2025 school year',
                'academic_year' => '2024-2025',
                'academic_year_start' => 2024,
                'academic_year_end' => 2025,
                'start_date' => '2024-09-02',
                'end_date' => '2024-12-20',
                'registration_start_date' => '2024-08-01',
                'registration_end_date' => '2024-08-31',
                'term_type' => 'first',
                'duration_weeks' => 15,
                'total_instructional_days' => 75,
                'total_instructional_hours' => 450,
                'is_grading_period' => true,
                'is_exam_period' => true,
                'mid_term_start_date' => '2024-10-21',
                'mid_term_end_date' => '2024-11-01',
                'final_exam_start_date' => '2024-12-09',
                'final_exam_end_date' => '2024-12-20',
                'status' => 'completed',
                'is_current_term' => false,
                'is_next_term' => false,
                'tuition_fee' => 500000.00,
                'other_fees' => 100000.00,
                'fee_due_date' => '2024-09-30',
                'late_fee_applicable' => true,
                'late_fee_amount' => 50000.00,
                'late_fee_days' => 30,
                'holidays' => [
                    '2024-10-09' => 'Independence Day',
                    '2024-11-01' => 'All Saints Day',
                    '2024-12-25' => 'Christmas Day',
                ],
                'special_events' => [
                    '2024-09-15' => 'Parent-Teacher Meeting',
                    '2024-11-15' => 'Sports Day',
                    '2024-12-15' => 'End of Term Celebration',
                ],
                'notes' => 'First term completed successfully with good student performance.',
                'announcements' => 'Welcome to the new academic year! Please ensure all fees are paid by the due date.',
            ],
            [
                'name' => 'Second Term 2024-2025',
                'code' => 'T2-2024',
                'description' => 'Second academic term of the 2024-2025 school year',
                'academic_year' => '2024-2025',
                'academic_year_start' => 2024,
                'academic_year_end' => 2025,
                'start_date' => '2025-01-13',
                'end_date' => '2025-04-04',
                'registration_start_date' => '2025-01-01',
                'registration_end_date' => '2025-01-10',
                'term_type' => 'second',
                'duration_weeks' => 12,
                'total_instructional_days' => 60,
                'total_instructional_hours' => 360,
                'is_grading_period' => true,
                'is_exam_period' => true,
                'mid_term_start_date' => '2025-02-24',
                'mid_term_end_date' => '2025-03-07',
                'final_exam_start_date' => '2025-03-24',
                'final_exam_end_date' => '2025-04-04',
                'status' => 'active',
                'is_current_term' => true,
                'is_next_term' => false,
                'tuition_fee' => 500000.00,
                'other_fees' => 100000.00,
                'fee_due_date' => '2025-02-15',
                'late_fee_applicable' => true,
                'late_fee_amount' => 50000.00,
                'late_fee_days' => 30,
                'holidays' => [
                    '2025-01-26' => 'Republic Day',
                    '2025-02-14' => 'Valentine\'s Day',
                    '2025-03-08' => 'International Women\'s Day',
                ],
                'special_events' => [
                    '2025-01-20' => 'Science Fair',
                    '2025-02-20' => 'Cultural Day',
                    '2025-03-20' => 'Career Guidance Day',
                ],
                'notes' => 'Second term is currently in progress. Students are performing well.',
                'announcements' => 'Mid-term examinations are approaching. Please ensure students are well prepared.',
            ],
            [
                'name' => 'Third Term 2024-2025',
                'code' => 'T3-2024',
                'description' => 'Third academic term of the 2024-2025 school year',
                'academic_year' => '2024-2025',
                'academic_year_start' => 2024,
                'academic_year_end' => 2025,
                'start_date' => '2025-04-28',
                'end_date' => '2025-07-25',
                'registration_start_date' => '2025-04-15',
                'registration_end_date' => '2025-04-25',
                'term_type' => 'third',
                'duration_weeks' => 13,
                'total_instructional_days' => 65,
                'total_instructional_hours' => 390,
                'is_grading_period' => true,
                'is_exam_period' => true,
                'mid_term_start_date' => '2025-06-02',
                'mid_term_end_date' => '2025-06-13',
                'final_exam_start_date' => '2025-07-14',
                'final_exam_end_date' => '2025-07-25',
                'status' => 'draft',
                'is_current_term' => false,
                'is_next_term' => true,
                'tuition_fee' => 500000.00,
                'other_fees' => 100000.00,
                'fee_due_date' => '2025-05-31',
                'late_fee_applicable' => true,
                'late_fee_amount' => 50000.00,
                'late_fee_days' => 30,
                'holidays' => [
                    '2025-05-01' => 'Labour Day',
                    '2025-06-21' => 'International Yoga Day',
                    '2025-07-04' => 'Independence Day',
                ],
                'special_events' => [
                    '2025-05-15' => 'Annual Sports Meet',
                    '2025-06-15' => 'Art Exhibition',
                    '2025-07-20' => 'Graduation Ceremony',
                ],
                'notes' => 'Third term planning is in progress. This will be the final term of the academic year.',
                'announcements' => 'Third term registration will open soon. Please prepare for the upcoming academic activities.',
            ],
        ];

        foreach ($terms as $termData) {
            Term::firstOrCreate(
                ['code' => $termData['code']],
                array_merge($termData, [
                    'business_id' => $business->id,
                    'created_by' => $adminUser->id,
                ])
            );
        }

        $this->command->info('Sample academic terms created successfully.');

        // Create a summer term for special programs
        Term::firstOrCreate(
            ['code' => 'SUMMER-2024'],
            [
                'business_id' => $business->id,
                'created_by' => $adminUser->id,
                'name' => 'Summer Term 2024',
                'description' => 'Special summer term for remedial classes and enrichment programs',
                'academic_year' => '2024-2025',
                'academic_year_start' => 2024,
                'academic_year_end' => 2025,
                'start_date' => '2024-12-23',
                'end_date' => '2025-01-10',
                'registration_start_date' => '2024-12-01',
                'registration_end_date' => '2024-12-20',
                'term_type' => 'summer',
                'duration_weeks' => 3,
                'total_instructional_days' => 15,
                'total_instructional_hours' => 90,
                'is_grading_period' => false,
                'is_exam_period' => false,
                'status' => 'completed',
                'is_current_term' => false,
                'is_next_term' => false,
                'tuition_fee' => 200000.00,
                'other_fees' => 50000.00,
                'fee_due_date' => '2024-12-15',
                'late_fee_applicable' => false,
                'holidays' => [
                    '2024-12-25' => 'Christmas Day',
                    '2024-12-26' => 'Boxing Day',
                    '2025-01-01' => 'New Year\'s Day',
                ],
                'special_events' => [
                    '2024-12-30' => 'Summer Camp Activities',
                    '2025-01-05' => 'Talent Show',
                ],
                'notes' => 'Summer term completed successfully. Students participated in various enrichment activities.',
                'announcements' => 'Summer term was a great success! Students improved their skills through various activities.',
            ]
        );

        $this->command->info('Summer term created successfully.');

        // Display summary
        $totalTerms = Term::where('business_id', $business->id)->count();
        $activeTerms = Term::where('business_id', $business->id)->where('status', 'active')->count();
        $completedTerms = Term::where('business_id', $business->id)->where('status', 'completed')->count();
        $draftTerms = Term::where('business_id', $business->id)->where('status', 'draft')->count();

        $this->command->info("Term Management setup completed successfully!");
        $this->command->info("Total terms created: {$totalTerms}");
        $this->command->info("Active terms: {$activeTerms}");
        $this->command->info("Completed terms: {$completedTerms}");
        $this->command->info("Draft terms: {$draftTerms}");
    }
}
