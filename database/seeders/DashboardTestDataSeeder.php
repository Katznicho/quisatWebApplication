<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\User;
use App\Models\BusinessCategory;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DashboardTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create business categories
        $categories = [
            'School' => 'Educational institutions',
            'Church' => 'Religious organizations',
            'Events' => 'Event management companies',
            'Shop' => 'Retail businesses'
        ];

        foreach ($categories as $name => $description) {
            BusinessCategory::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Create businesses for each category
        $businesses = [
            [
                'name' => 'Kampala International School',
                'email' => 'info@kis.edu.ug',
                'phone' => '256700000001',
                'address' => 'Kampala, Uganda',
                'country' => 'Uganda',
                'city' => 'Kampala',
                'category' => 'School',
                'users_count' => 15
            ],
            [
                'name' => 'St. Mary\'s Church',
                'email' => 'info@stmarys.ug',
                'phone' => '256700000002',
                'address' => 'Kampala, Uganda',
                'country' => 'Uganda',
                'city' => 'Kampala',
                'category' => 'Church',
                'users_count' => 8
            ],
            [
                'name' => 'Event Masters Ltd',
                'email' => 'info@eventmasters.ug',
                'phone' => '256700000003',
                'address' => 'Kampala, Uganda',
                'country' => 'Uganda',
                'city' => 'Kampala',
                'category' => 'Events',
                'users_count' => 12
            ],
            [
                'name' => 'Super Mart',
                'email' => 'info@supermart.ug',
                'phone' => '256700000004',
                'address' => 'Kampala, Uganda',
                'country' => 'Uganda',
                'city' => 'Kampala',
                'category' => 'Shop',
                'users_count' => 6
            ],
            [
                'name' => 'Wakiso High School',
                'email' => 'info@whs.edu.ug',
                'phone' => '256700000005',
                'address' => 'Wakiso, Uganda',
                'country' => 'Uganda',
                'city' => 'Wakiso',
                'category' => 'School',
                'users_count' => 20
            ],
            [
                'name' => 'Nairobi Business Center',
                'email' => 'info@nbc.ke',
                'phone' => '254700000001',
                'address' => 'Nairobi, Kenya',
                'country' => 'Kenya',
                'city' => 'Nairobi',
                'category' => 'Shop',
                'users_count' => 10
            ]
        ];

        foreach ($businesses as $businessData) {
            $category = BusinessCategory::where('name', $businessData['category'])->first();
            
            $business = Business::firstOrCreate(
                ['email' => $businessData['email']],
                [
                    'name' => $businessData['name'],
                    'phone' => $businessData['phone'],
                    'address' => $businessData['address'],
                    'country' => $businessData['country'],
                    'city' => $businessData['city'],
                    'business_category_id' => $category->id,
                    'account_number' => 'KS' . time() . rand(100, 999),
                ]
            );

            // Create users for this business
            for ($i = 1; $i <= $businessData['users_count']; $i++) {
                $userData = [
                    'name' => "User {$i} - {$businessData['name']}",
                    'email' => "user{$i}@" . strtolower(str_replace(' ', '', $businessData['name'])) . ".com",
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'business_id' => $business->id,
                ];

                // Create or get a default role for this business
                $roleNames = ['Parents', 'Teachers', 'Business Admins', 'Other Users'];
                $roleName = $roleNames[array_rand($roleNames)];
                
                $role = Role::firstOrCreate(
                    [
                        'business_id' => $business->id,
                        'name' => $roleName . ' - ' . $business->id
                    ],
                    [
                        'description' => $roleName . ' role for ' . $business->name,
                        'permissions' => json_encode([])
                    ]
                );

                // Create user with random creation date within the last year
                $randomDate = Carbon::now()->subDays(rand(0, 365));
                $user = User::firstOrCreate(
                    ['email' => $userData['email']],
                    array_merge($userData, [
                        'role_id' => $role->id,
                        'created_at' => $randomDate, 
                        'updated_at' => $randomDate
                    ])
                );
            }
        }

        $this->command->info('Dashboard test data seeded successfully!');
    }
}
