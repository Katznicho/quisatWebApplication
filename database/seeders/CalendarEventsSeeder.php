<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\CalendarEvent;
use App\Models\EventNotification;
use App\Models\Business;
use App\Models\User;
use App\Models\Role;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\ParentGuardian;
use Carbon\Carbon;

class CalendarEventsSeeder extends Seeder
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

        // Create Calendar & Events feature
        $calendarFeature = Feature::firstOrCreate(
            ['name' => 'Calendar & Events Management'],
            [
                'description' => 'Comprehensive calendar and event management system with advanced notification capabilities',
                'price' => 80.00,
                'currency_id' => 1,
            ]
        );

        // Get admin user
        $adminUser = User::where('business_id', $business->id)
            ->whereHas('role', function($query) {
                $query->where('name', 'Admin');
            })
            ->first();

        if (!$adminUser) {
            $this->command->error('No admin user found.');
            return;
        }

        // Create sample calendar events
        $events = [
            [
                'title' => 'Parent-Teacher Meeting',
                'description' => 'Annual parent-teacher meeting to discuss student progress and academic performance.',
                'start_date' => Carbon::now()->addDays(7)->setTime(14, 0),
                'end_date' => Carbon::now()->addDays(7)->setTime(16, 0),
                'location' => 'School Auditorium',
                'color' => '#3B82F6',
                'event_type' => 'meeting',
                'priority' => 'high',
                'is_all_day' => false,
                'status' => 'published',
            ],
            [
                'title' => 'Annual Sports Day',
                'description' => 'Annual sports competition featuring various athletic events and team competitions.',
                'start_date' => Carbon::now()->addDays(14)->setTime(9, 0),
                'end_date' => Carbon::now()->addDays(14)->setTime(17, 0),
                'location' => 'School Grounds',
                'color' => '#10B981',
                'event_type' => 'activity',
                'priority' => 'medium',
                'is_all_day' => true,
                'status' => 'published',
            ],
            [
                'title' => 'Mid-Term Examinations',
                'description' => 'Mid-term examinations for all classes. Students should prepare thoroughly.',
                'start_date' => Carbon::now()->addDays(21)->setTime(8, 0),
                'end_date' => Carbon::now()->addDays(25)->setTime(16, 0),
                'location' => 'Classrooms',
                'color' => '#EF4444',
                'event_type' => 'exam',
                'priority' => 'urgent',
                'is_all_day' => false,
                'status' => 'published',
            ],
            [
                'title' => 'Staff Development Workshop',
                'description' => 'Professional development workshop for teachers on modern teaching methodologies.',
                'start_date' => Carbon::now()->addDays(3)->setTime(10, 0),
                'end_date' => Carbon::now()->addDays(3)->setTime(15, 0),
                'location' => 'Conference Room',
                'color' => '#8B5CF6',
                'event_type' => 'meeting',
                'priority' => 'medium',
                'is_all_day' => false,
                'status' => 'published',
            ],
            [
                'title' => 'School Holiday - Independence Day',
                'description' => 'School will be closed for Independence Day celebrations.',
                'start_date' => Carbon::now()->addDays(30)->setTime(0, 0),
                'end_date' => Carbon::now()->addDays(30)->setTime(23, 59),
                'location' => 'School Closed',
                'color' => '#F59E0B',
                'event_type' => 'holiday',
                'priority' => 'low',
                'is_all_day' => true,
                'status' => 'published',
            ],
        ];

        foreach ($events as $eventData) {
            $event = CalendarEvent::create(array_merge($eventData, [
                'business_id' => $business->id,
                'created_by' => $adminUser->id,
            ]));

            // Create notifications for each event
            $this->createEventNotifications($event, $business);
        }

        $this->command->info('Calendar & Events feature and sample data created successfully!');
    }

    private function createEventNotifications($event, $business)
    {
        $notificationTypes = [
            [
                'title' => "Reminder: {$event->title}",
                'message' => "This is a reminder for the upcoming event: {$event->title}. Please make sure to attend.",
                'notification_type' => 'email',
                'target_type' => 'all',
                'reminder_minutes' => 1440, // 24 hours before
            ],
            [
                'title' => "Event Update: {$event->title}",
                'message' => "There has been an update to the event: {$event->title}. Please check the details.",
                'notification_type' => 'in_app',
                'target_type' => 'specific_roles',
                'target_ids' => [Role::where('name', 'Staff')->first()->id],
                'reminder_minutes' => 60, // 1 hour before
            ],
        ];

        // Special notifications for specific event types
        if ($event->event_type === 'exam') {
            $notificationTypes[] = [
                'title' => "Exam Preparation: {$event->title}",
                'message' => "Students, please prepare for the upcoming examination. Good luck!",
                'notification_type' => 'email',
                'target_type' => 'specific_students',
                'target_ids' => Student::where('business_id', $business->id)->pluck('id')->toArray(),
                'reminder_minutes' => 2880, // 48 hours before
            ];
        }

        if ($event->event_type === 'meeting') {
            $notificationTypes[] = [
                'title' => "Meeting Invitation: {$event->title}",
                'message' => "You are invited to attend: {$event->title}. Your presence is important.",
                'notification_type' => 'email',
                'target_type' => 'specific_parents',
                'target_ids' => ParentGuardian::where('business_id', $business->id)->pluck('id')->toArray(),
                'reminder_minutes' => 720, // 12 hours before
            ];
        }

        foreach ($notificationTypes as $notificationData) {
            EventNotification::create(array_merge($notificationData, [
                'calendar_event_id' => $event->id,
                'business_id' => $business->id,
                'status' => 'pending',
                'scheduled_at' => $event->start_date->subMinutes($notificationData['reminder_minutes']),
            ]));
        }
    }
}
