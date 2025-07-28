<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\BusinessCategory;

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

        // Step 1: Create the MarzPay business
        $business = Business::create([
            'name' => 'Quisat School',
            'email' => 'katznicho@gmail.com',
            'phone' => '256700000001',
            'address' => 'Kampala, Uganda',
            'logo' => 'logos/marzpay.png',
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
        User::create([
            'name' => 'Quisat Admin',
            'email' => 'katznicho@gmail.com',
            'password' => Hash::make('password'), // change this in production
            'status' => 'active',
            'business_id' => $business->id,
            'role_id' => $role->id,
        ]);
    }
} 