<?php

namespace App\Actions\Fortify;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    // public function create(array $input): User
    // {
    //     Validator::make($input, [
    //         'name' => ['required', 'string', 'max:255'],
    //         'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
    //         'password' => $this->passwordRules(),
    //         'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
    //     ])->validate();

    //     return User::create([
    //         'name' => $input['name'],
    //         'email' => $input['email'],
    //         'password' => Hash::make($input['password']),
    //     ]);
    // }

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();
    
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'user_type' => 'user',
            'balance' => 0,
            'status' => 'active',
            'raw_password' => $input['password'],
        ]);
    
        // Try to send the welcome email, but ignore any failure
        try {
            //    ->cc(['katznicho@gmail.com', 'nextgentraders5@gmail.com'])
            Mail::to($user->email)
            ->cc(['katznicho@gmail.com', 'nextgentraders5@gmail.com'])
            ->send(new WelcomeEmail($user));
        } catch (\Exception $e) {
            // Optionally log the error
            Log::error('Failed to send welcome email: ' . $e->getMessage());
        }
    
        return $user;
    }
    
}
