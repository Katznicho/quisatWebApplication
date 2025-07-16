<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MarzPaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 1: Create the MarzPay business
        $business = Business::create([
            'name' => 'Kashtre',
            'email' => 'katznicho@gmail.com',
            'phone' => '256700000001',
            'address' => 'Kampala, Uganda',
            'logo' => 'logos/marzpay.png',
            'account_number' => 'KS12345678',
        ]);

        // Step 2: Create a default branch for the business
        $branch = Branch::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'email' => 'main@kashtre.com',
            'phone' => '256700000002',
            'address' => 'Head Office, Kampala',
        ]);

        // Step 3: Create a default user assigned to the business and branch
        User::create([
            'name' => 'Kashtre Admin',
            'email' => 'katznicho@gmail.com',
            'password' => Hash::make('password'), // change this in production
            'status' => 'active',
            'business_id' => $business->id,
            'branch_id' => $branch->id,
        ]);
    }
}
