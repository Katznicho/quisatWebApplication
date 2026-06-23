<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\BusinessCategory;
use App\Models\BusinessRegistrationDocument;
use App\Models\Country;
use App\Mail\BusinessWelcomeEmail;
use App\Mail\BusinessAdminWelcomeEmail;
use App\Mail\NewBusinessRegisteredMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rule;

class BusinessRegistrationController extends Controller
{
    public function showRegistrationForm()
    {
        $businessCategories = BusinessCategory::orderBy('name')->get();
        $countries = Country::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('businesses.register', compact('businessCategories', 'countries'));
    }

    public function categoryDocuments($categoryId)
    {
        $category = BusinessCategory::findOrFail($categoryId);

        $documents = $category->requiredDocumentTypesForAccount('business')->map(function ($documentType) {
            return [
                'id' => $documentType->id,
                'name' => $documentType->name,
                'description' => $documentType->description,
                'is_required' => (bool) $documentType->pivot->is_required,
            ];
        })->values();

        return response()->json(['documents' => $documents]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'business_email' => [
                'required',
                'email',
                Rule::unique('businesses', 'email')->whereNull('deleted_at'),
            ],
            'business_phone' => 'required|string|max:20',
            'business_address' => 'required|string|max:255',
            'business_country_id' => 'required|exists:countries,id',
            'business_city' => 'required|string|max:255',
            'business_category_id' => 'required|exists:business_categories,id',
            'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'website_link' => 'nullable|url|max:255',
            'social_facebook' => 'nullable|url|max:255',
            'social_instagram' => 'nullable|url|max:255',
            'social_twitter' => 'nullable|url|max:255',
            'social_whatsapp' => 'nullable|string|max:255',

            // Admin user details — ignore emails tied only to soft-deleted businesses
            'admin_name' => 'required|string|max:255',
            'admin_email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->where(function ($query) {
                    $query->where(function ($inner) {
                        $inner->whereNull('business_id')
                            ->orWhereIn('business_id', Business::query()->select('id'));
                    });
                }),
            ],
            'admin_password' => 'required|string|min:8|confirmed',
            'admin_phone' => 'required|string|max:20',
        ]);

        $category = BusinessCategory::with(['documentTypes' => fn ($query) => $query->where('document_types.is_active', true)])
            ->find($request->business_category_id);

        if ($category) {
            foreach ($category->requiredDocumentTypesForAccount('business') as $documentType) {
                $rule = ((bool) $documentType->pivot->is_required) ? 'required' : 'nullable';
                $validator->addRules([
                    "documents.{$documentType->id}" => "{$rule}|file|mimes:pdf,jpg,jpeg,png|max:5120",
                ]);
            }
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->releaseEmailsFromDeletedBusinesses(
            $request->business_email,
            $request->admin_email,
        );

        try {
            DB::beginTransaction();

            // Handle business logo upload
            $logoPath = null;
            if ($request->hasFile('business_logo')) {
                $logoPath = $request->file('business_logo')->store('business_logos', 'public');
            }

            // Prepare social media handles
            $socialMediaHandles = [];
            if ($request->filled('social_facebook')) {
                $socialMediaHandles['facebook'] = $request->social_facebook;
            }
            if ($request->filled('social_instagram')) {
                $socialMediaHandles['instagram'] = $request->social_instagram;
            }
            if ($request->filled('social_twitter')) {
                $socialMediaHandles['twitter'] = $request->social_twitter;
            }
            if ($request->filled('social_whatsapp')) {
                $socialMediaHandles['whatsapp'] = $request->social_whatsapp;
            }

            // Create business
            $country = Country::findOrFail((int) $request->business_country_id);

            $business = Business::create([
                'name' => $request->business_name,
                'email' => $request->business_email,
                'phone' => $request->business_phone,
                'address' => $request->business_address,
                'country_id' => $country->id,
                'country' => $country->name,
                'currency_code' => $country->currency_code,
                'exchange_rate' => $country->exchange_rate,
                'city' => $request->business_city,
                'business_category_id' => $request->business_category_id,
                'logo' => $logoPath,
                'website_link' => $request->website_link,
                'social_media_handles' => !empty($socialMediaHandles) ? $socialMediaHandles : null,
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

            // Fire the Registered event to trigger email verification
            event(new Registered($adminUser));

            if ($category) {
                foreach ($category->requiredDocumentTypesForAccount('business') as $documentType) {
                    $uploadedFile = $request->file("documents.{$documentType->id}");

                    if (! $uploadedFile) {
                        continue;
                    }

                    $storedPath = $uploadedFile->store('business_registration_documents', 'public');

                    BusinessRegistrationDocument::create([
                        'business_id' => $business->id,
                        'document_type_id' => $documentType->id,
                        'file_path' => $storedPath,
                        'original_filename' => $uploadedFile->getClientOriginalName(),
                        'mime_type' => $uploadedFile->getClientMimeType(),
                    ]);
                }
            }

            DB::commit();

            $this->sendRegistrationEmails($business, $adminUser);

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

    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            
            if ($user && !$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
                
                return redirect()->back()->with('success', 'Verification email has been sent to ' . $user->email);
            } else {
                return redirect()->back()->with('error', 'Email not found or already verified.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to resend verification email: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send verification email. Please try again.');
        }
    }

    /**
     * Send welcome and internal notification emails after registration.
     * Each message is sent independently so one failure does not block the others.
     */
    protected function sendRegistrationEmails(Business $business, User $adminUser): void
    {
        try {
            Mail::to($business->email)->send(new BusinessWelcomeEmail($business));
        } catch (\Exception $e) {
            Log::error('Failed to send business welcome email', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            Mail::to($adminUser->email)->send(new BusinessAdminWelcomeEmail($adminUser, $business));
        } catch (\Exception $e) {
            Log::error('Failed to send business admin welcome email', [
                'user_id' => $adminUser->id,
                'error' => $e->getMessage(),
            ]);
        }

        $notifyAddresses = config('mail.business_registration_notify', []);
        if (empty($notifyAddresses)) {
            return;
        }

        try {
            Mail::to($notifyAddresses)->send(new NewBusinessRegisteredMail($business, $adminUser));
            Log::info('Business registration notification sent', [
                'business_id' => $business->id,
                'to' => $notifyAddresses,
                'cc' => config('mail.business_registration_notify_cc', []),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send business registration notification emails', [
                'business_id' => $business->id,
                'to' => $notifyAddresses,
                'cc' => config('mail.business_registration_notify_cc', []),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Free business/admin emails held by soft-deleted businesses so they can register again.
     */
    protected function releaseEmailsFromDeletedBusinesses(string $businessEmail, string $adminEmail): void
    {
        $businessIdsFromAdmin = User::query()
            ->where('email', $adminEmail)
            ->whereNotNull('business_id')
            ->pluck('business_id');

        $trashedBusinessIds = Business::onlyTrashed()
            ->where(function ($query) use ($businessEmail, $businessIdsFromAdmin) {
                $query->where('email', $businessEmail);

                if ($businessIdsFromAdmin->isNotEmpty()) {
                    $query->orWhereIn('id', $businessIdsFromAdmin);
                }
            })
            ->pluck('id');

        if ($trashedBusinessIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('business_id', $trashedBusinessIds)
            ->where('email', $adminEmail)
            ->delete();

        Business::onlyTrashed()
            ->whereIn('id', $trashedBusinessIds)
            ->where('email', $businessEmail)
            ->get()
            ->each(function (Business $business) {
                $business->forceFill([
                    'email' => 'deleted_'.$business->id.'_'.time().'@deleted.quisat.local',
                ])->save();
            });
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
