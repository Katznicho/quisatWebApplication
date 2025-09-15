<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Advertisement;
use App\Models\User;
use App\Models\Business;
use Carbon\Carbon;

class AdvertisementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first business and admin user
        $business = Business::first();
        $adminUser = User::whereHas('role', function($query) {
            $query->where('name', 'Admin');
        })->first();

        if (!$business || !$adminUser) {
            $this->command->info('No business or admin user found. Skipping advertisement seeding.');
            return;
        }

        $advertisements = [
            [
                'title' => 'Welcome to Our School!',
                'description' => 'Join our amazing school community and give your child the best education experience.',
                'media_type' => 'image',
                'category' => 'promotion',
                'target_audience' => ['parents'],
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => Carbon::now()->addDays(30),
                'status' => 'active',
                'budget' => 500.00,
                'recurrence_pattern' => 'weekly',
                'is_recurring' => true,
            ],
            [
                'title' => 'Parent-Teacher Conference',
                'description' => 'Don\'t miss our upcoming parent-teacher conference. Book your appointment now!',
                'media_type' => 'text',
                'category' => 'event',
                'target_audience' => ['parents'],
                'start_date' => Carbon::now()->subDays(2),
                'end_date' => Carbon::now()->addDays(7),
                'status' => 'active',
                'budget' => 200.00,
                'recurrence_pattern' => null,
                'is_recurring' => false,
            ],
            [
                'title' => 'New Sports Program',
                'description' => 'Introducing our new sports program for students. Registration is now open!',
                'media_type' => 'image',
                'category' => 'announcement',
                'target_audience' => ['students'],
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(45),
                'status' => 'active',
                'budget' => 750.00,
                'recurrence_pattern' => 'monthly',
                'is_recurring' => true,
            ],
            [
                'title' => 'Staff Training Workshop',
                'description' => 'Professional development workshop for all staff members. Mandatory attendance.',
                'media_type' => 'text',
                'category' => 'announcement',
                'target_audience' => ['staff'],
                'start_date' => Carbon::now()->addDays(3),
                'end_date' => Carbon::now()->addDays(10),
                'status' => 'scheduled',
                'budget' => 300.00,
                'recurrence_pattern' => null,
                'is_recurring' => false,
            ],
            [
                'title' => 'School Holiday Notice',
                'description' => 'Important notice about upcoming school holidays and schedule changes.',
                'media_type' => 'text',
                'category' => 'announcement',
                'target_audience' => ['all_users'],
                'start_date' => Carbon::now()->subDays(1),
                'end_date' => Carbon::now()->addDays(14),
                'status' => 'active',
                'budget' => 100.00,
                'recurrence_pattern' => null,
                'is_recurring' => false,
            ],
            [
                'title' => 'Library Book Drive',
                'description' => 'Help us expand our library collection. Donate books and support education!',
                'media_type' => 'image',
                'category' => 'promotion',
                'target_audience' => ['all_users'],
                'start_date' => Carbon::now()->addDays(5),
                'end_date' => Carbon::now()->addDays(60),
                'status' => 'draft',
                'budget' => 400.00,
                'recurrence_pattern' => 'weekly',
                'is_recurring' => true,
            ],
        ];

        foreach ($advertisements as $adData) {
            Advertisement::create([
                'business_id' => $business->id,
                'created_by' => $adminUser->id,
                ...$adData
            ]);
        }

        $this->command->info('Created ' . count($advertisements) . ' sample advertisements.');
    }
}