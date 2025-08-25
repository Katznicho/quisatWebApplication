<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Models\BusinessCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BusinessRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_registration_form_can_be_rendered()
    {
        $response = $this->get('/business/register');

        $response->assertStatus(200);
        $response->assertSee('Register Your Business');
    }

    public function test_business_can_be_registered_with_valid_data()
    {
        Mail::fake();

        // Create a business category first
        $category = BusinessCategory::factory()->create();

        $businessData = [
            'business_name' => 'Test Business',
            'business_email' => 'business@test.com',
            'business_phone' => '+1234567890',
            'business_address' => '123 Test Street',
            'business_country' => 'Test Country',
            'business_city' => 'Test City',
            'business_category_id' => $category->id,
            
            'admin_name' => 'Admin User',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'admin_phone' => '+1234567890',
        ];

        $response = $this->post('/business/register', $businessData);

        $response->assertRedirect('/business/registration/success');
        $response->assertSessionHas('success');

        // Assert business was created
        $this->assertDatabaseHas('businesses', [
            'name' => 'Test Business',
            'email' => 'business@test.com',
        ]);

        // Assert admin user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        // Assert admin role was created
        $business = Business::where('email', 'business@test.com')->first();
        $this->assertDatabaseHas('roles', [
            'business_id' => $business->id,
            'name' => 'Admin',
        ]);
    }

    public function test_business_registration_validates_required_fields()
    {
        $response = $this->post('/business/register', []);

        $response->assertSessionHasErrors([
            'business_name',
            'business_email',
            'business_phone',
            'business_address',
            'business_country',
            'business_city',
            'business_category_id',
            'admin_name',
            'admin_email',
            'admin_password',
            'admin_phone',
        ]);
    }

    public function test_business_registration_validates_unique_emails()
    {
        // Create existing business and user
        Business::factory()->create(['email' => 'existing@business.com']);
        User::factory()->create(['email' => 'existing@user.com']);

        $category = BusinessCategory::factory()->create();

        $response = $this->post('/business/register', [
            'business_name' => 'Test Business',
            'business_email' => 'existing@business.com', // Duplicate
            'business_phone' => '+1234567890',
            'business_address' => '123 Test Street',
            'business_country' => 'Test Country',
            'business_city' => 'Test City',
            'business_category_id' => $category->id,
            
            'admin_name' => 'Admin User',
            'admin_email' => 'existing@user.com', // Duplicate
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'admin_phone' => '+1234567890',
        ]);

        $response->assertSessionHasErrors([
            'business_email',
            'admin_email',
        ]);
    }

    public function test_business_registration_success_page_can_be_rendered()
    {
        $response = $this->get('/business/registration/success');

        $response->assertStatus(200);
        $response->assertSee('Registration Successful');
    }
}
