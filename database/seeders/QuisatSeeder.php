<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\BusinessCategory;
use App\Models\Program;
use App\Models\ProgramEvent;
use App\Models\Currency;

class QuisatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 0: Create a default business category
        $businessCategory = BusinessCategory::create([
            'name' => 'School',
            'description' => 'School business category',
        ]);

        // Step 1: Create the Quisat business
        $business = Business::create([
            'name' => 'Quisat School',
            'email' => 'katznicho@gmail.com',
            'phone' => '256700000001',
            'address' => 'Kampala, Uganda',
            'country' => 'Uganda',
            'city' => 'Kampala',
            'logo' => 'logos/quisat.png',
            'account_number' => 'KS12345678',
            'business_category_id' => $businessCategory->id,
        ]);

        // Step 1.1: Create a default role for the business
        $role = Role::create([
            'name' => 'Admin',
            'description' => 'Admin role',
            'business_id' => $business->id,
            'permissions' => json_encode(['']),
        ]);

        // Step 3: Create a default user assigned to the business and branch
        $user = User::create([
            'name' => 'Quisat Admin',
            'email' => 'katznicho@gmail.com',
            'password' => Hash::make('password'), // change this in production
            'status' => 'active',
            'business_id' => $business->id,
            'role_id' => $role->id,
        ]);

        // Step 4: Create a default currency
        $currency = Currency::create([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1.0,
        ]);

        // Step 5: Create sample programs
        $bibleAdventure = Program::create([
            'name' => 'Bible Adventure',
            'description' => 'An exciting journey through biblical stories for children',
            'age-group' => '5-12 years',
            'status' => 'active',
        ]);

        $sundaySchool = Program::create([
            'name' => 'Sunday School',
            'description' => 'Weekly Sunday school program for children',
            'age-group' => '3-15 years',
            'status' => 'active',
        ]);

        // Step 6: Create sample events
        ProgramEvent::create([
            'program_ids' => [$bibleAdventure->id],
            'name' => 'Creation Week',
            'description' => 'Learn about the seven days of creation',
            'start_date' => '2025-04-10',
            'end_date' => '2025-04-10',
            'price' => 100.00,
            'status' => 'open',
            'location' => 'Church Hall A',
            'currency_id' => $currency->id,
            'business_id' => $business->id,
            'user_id' => $user->id,
        ]);

        ProgramEvent::create([
            'program_ids' => [$bibleAdventure->id],
            'name' => 'Noah\'s Ark Day',
            'description' => 'Discover the story of Noah and the great flood',
            'start_date' => '2025-05-12',
            'end_date' => '2025-05-12',
            'price' => 100.00,
            'status' => 'upcoming',
            'location' => 'Church Playground',
            'currency_id' => $currency->id,
            'business_id' => $business->id,
            'user_id' => $user->id,
        ]);
    }
} 