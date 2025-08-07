<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramEvent;
use App\Models\Currency;
use App\Models\Business;
use App\Models\User;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing business and user
        $business = Business::first();
        $user = User::first();
        
        if (!$business || !$user) {
            $this->command->error('Business or User not found. Please run QuisatSeeder first.');
            return;
        }

        // Create or get currency
        $currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 1.0,
            ]
        );

        // Create sample programs
        $bibleAdventure = Program::firstOrCreate(
            ['name' => 'Bible Adventure'],
            [
                'description' => 'An exciting journey through biblical stories for children',
                'age-group' => '5-12 years',
                'status' => 'active',
            ]
        );

        $sundaySchool = Program::firstOrCreate(
            ['name' => 'Sunday School'],
            [
                'description' => 'Weekly Sunday school program for children',
                'age-group' => '3-15 years',
                'status' => 'active',
            ]
        );

        // Create sample events
        ProgramEvent::firstOrCreate(
            ['name' => 'Creation Week'],
            [
                'program_ids' => [$bibleAdventure->id],
                'description' => 'Learn about the seven days of creation',
                'start_date' => '2025-04-10',
                'end_date' => '2025-04-10',
                'price' => 100.00,
                'status' => 'open',
                'location' => 'Church Hall A',
                'currency_id' => $currency->id,
                'business_id' => $business->id,
                'user_id' => $user->id,
            ]
        );

        ProgramEvent::firstOrCreate(
            ['name' => 'Noah\'s Ark Day'],
            [
                'program_ids' => [$bibleAdventure->id],
                'description' => 'Discover the story of Noah and the great flood',
                'start_date' => '2025-05-12',
                'end_date' => '2025-05-12',
                'price' => 100.00,
                'status' => 'upcoming',
                'location' => 'Church Playground',
                'currency_id' => $currency->id,
                'business_id' => $business->id,
                'user_id' => $user->id,
            ]
        );

        $this->command->info('Programs and events seeded successfully!');
    }
} 