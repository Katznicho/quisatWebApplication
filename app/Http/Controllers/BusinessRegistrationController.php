<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\BusinessCategory;
use App\Mail\BusinessWelcomeEmail;
use App\Mail\BusinessAdminWelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BusinessRegistrationController extends Controller
{
    public function showRegistrationForm()
    {
        $businessCategories = BusinessCategory::all();
        return view('businesses.register', compact('businessCategories'));
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'business_email' => 'required|email|unique:businesses,email',
            'business_phone' => 'required|string|max:20',
            'business_address' => 'required|string|max:255',
            'business_country' => 'required|string|max:255',
            'business_city' => 'required|string|max:255',
            'business_category_id' => 'required|exists:business_categories,id',
            'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            
            // Admin user details
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
            'admin_phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Handle business logo upload
            $logoPath = null;
            if ($request->hasFile('business_logo')) {
                $logoPath = $request->file('business_logo')->store('business_logos', 'public');
            }

            // Create business
            $business = Business::create([
                'name' => $request->business_name,
                'email' => $request->business_email,
                'phone' => $request->business_phone,
                'address' => $request->business_address,
                'country' => $request->business_country,
                'city' => $request->business_city,
                'business_category_id' => $request->business_category_id,
                'logo' => $logoPath,
                'account_number' => 'KS' . time(),
                'account_balance' => 0,
                'mode' => 'live',
                'date' => now(),
            ]);

            // Create default branch for the business
            $defaultBranch = \App\Models\Branch::create([
                'name' => 'Main Branch',
                'code' => 'MB-' . $business->id,
                'address' => $business->address,
                'phone' => $business->phone,
                'email' => $business->email,
                'business_id' => $business->id,
                'status' => 'active',
            ]);

            // Create admin role for the business
            $adminRole = Role::create([
                'business_id' => $business->id,
                'name' => 'Admin',
                'description' => 'Business Administrator with full access',
                'permissions' => [
                    'users.manage',
                    'transactions.view',
                    'transactions.create',
                    'reports.view',
                    'settings.manage',
                    'business.profile',
                ],
            ]);

            // Create admin user
            $adminUser = User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'status' => 'active',
                'business_id' => $business->id,
                'role_id' => $adminRole->id,
                'branch_id' => $defaultBranch->id,
            ]);

            DB::commit();

            // Send welcome emails
            try {
                // Send business welcome email
                Mail::to($business->email)->send(new BusinessWelcomeEmail($business));
                
                // Send admin welcome email
                Mail::to($adminUser->email)->send(new BusinessAdminWelcomeEmail($adminUser, $business));
            } catch (\Exception $e) {
                Log::error('Failed to send welcome emails: ' . $e->getMessage());
                // Don't fail the registration if email sending fails
            }

            return redirect()->route('business.registration.success')
                ->with('success', 'Business registered successfully! Please check your email to verify your account.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Business registration failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Registration failed. Please try again.')
                ->withInput();
        }
    }

    public function registrationSuccess()
    {
        return view('businesses.registration-success');
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->email), $hash)) {
            return redirect()->route('login')->with('error', 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('info', 'Email already verified.');
        }

        $user->markEmailAsVerified();

        return redirect()->route('login')->with('success', 'Email verified successfully! You can now log in.');
    }
}
