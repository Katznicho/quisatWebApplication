<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Business;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

class AdminAndStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a business category first
        $businessCategory = \App\Models\BusinessCategory::firstOrCreate(
            ['name' => 'System'],
            [
                'description' => 'System category for administrative purposes',
            ]
        );

        // Create a system business (business_id = 1)
        $systemBusiness = Business::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'System Administration',
                'email' => 'admin@system.com',
                'phone' => '+1234567890',
                'address' => 'System Address',
                'city' => 'System City',
                'country' => 'System Country',
                'account_number' => 'SYS001',
                'business_category_id' => $businessCategory->id,
                'status' => 'active',
            ]
        );

        // Create roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'System Admin'],
            [
                'business_id' => 1,
                'description' => 'System administrator with full access',
                'permissions' => ['*']
            ]
        );

        $businessAdminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            [
                'business_id' => 1,
                'description' => 'Business administrator',
                'permissions' => ['manage_business', 'manage_staff', 'view_reports', 'manage_programs']
            ]
        );

        $staffRole = Role::firstOrCreate(
            ['name' => 'Staff'],
            [
                'business_id' => 1,
                'description' => 'Staff member with limited permissions',
                'permissions' => ['view_reports', 'manage_own_profile']
            ]
        );

        // Create a system admin user
        User::firstOrCreate(
            ['email' => 'admin@system.com'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@system.com',
                'password' => Hash::make('password'),
                'business_id' => 1,
                'role_id' => $adminRole->id,
                'status' => 'active',
            ]
        );

        // Create some sample businesses
        $business1 = Business::firstOrCreate(
            ['name' => 'Tech Solutions Inc'],
            [
                'email' => 'info@techsolutions.com',
                'phone' => '+1234567891',
                'address' => '123 Tech Street',
                'city' => 'Tech City',
                'country' => 'Tech Country',
                'account_number' => 'TECH001',
                'business_category_id' => $businessCategory->id,
                'status' => 'active',
            ]
        );

        $business2 = Business::firstOrCreate(
            ['name' => 'Global Services Ltd'],
            [
                'email' => 'contact@globalservices.com',
                'phone' => '+1234567892',
                'address' => '456 Global Avenue',
                'city' => 'Global City',
                'country' => 'Global Country',
                'account_number' => 'GLOBAL001',
                'business_category_id' => $businessCategory->id,
                'status' => 'active',
            ]
        );

        // Create branches for businesses
        $branch1 = Branch::firstOrCreate(
            ['code' => 'TECH-MAIN'],
            [
                'name' => 'Main Branch',
                'code' => 'TECH-MAIN',
                'address' => '123 Tech Street',
                'phone' => '+1234567891',
                'email' => 'main@techsolutions.com',
                'business_id' => $business1->id,
                'status' => 'active',
            ]
        );

        $branch2 = Branch::firstOrCreate(
            ['code' => 'GLOBAL-HQ'],
            [
                'name' => 'Headquarters',
                'code' => 'GLOBAL-HQ',
                'address' => '456 Global Avenue',
                'phone' => '+1234567892',
                'email' => 'hq@globalservices.com',
                'business_id' => $business2->id,
                'status' => 'active',
            ]
        );

        // Create business admin users
        User::firstOrCreate(
            ['email' => 'admin@techsolutions.com'],
            [
                'name' => 'Tech Admin',
                'email' => 'admin@techsolutions.com',
                'password' => Hash::make('password'),
                'business_id' => $business1->id,
                'role_id' => $businessAdminRole->id,
                'branch_id' => $branch1->id,
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@globalservices.com'],
            [
                'name' => 'Global Admin',
                'email' => 'admin@globalservices.com',
                'password' => Hash::make('password'),
                'business_id' => $business2->id,
                'role_id' => $businessAdminRole->id,
                'branch_id' => $branch2->id,
                'status' => 'active',
            ]
        );

        // Create staff users
        User::firstOrCreate(
            ['email' => 'staff1@techsolutions.com'],
            [
                'name' => 'John Staff',
                'email' => 'staff1@techsolutions.com',
                'password' => Hash::make('password'),
                'business_id' => $business1->id,
                'role_id' => $staffRole->id,
                'branch_id' => $branch1->id,
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff2@techsolutions.com'],
            [
                'name' => 'Jane Staff',
                'email' => 'staff2@techsolutions.com',
                'password' => Hash::make('password'),
                'business_id' => $business1->id,
                'role_id' => $staffRole->id,
                'branch_id' => $branch1->id,
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff1@globalservices.com'],
            [
                'name' => 'Bob Staff',
                'email' => 'staff1@globalservices.com',
                'password' => Hash::make('password'),
                'business_id' => $business2->id,
                'role_id' => $staffRole->id,
                'branch_id' => $branch2->id,
                'status' => 'active',
            ]
        );

        $this->command->info('Admin and Staff data seeded successfully!');
        $this->command->info('System Admin: admin@system.com / password');
        $this->command->info('Tech Admin: admin@techsolutions.com / password');
        $this->command->info('Global Admin: admin@globalservices.com / password');
    }
}
