<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Business;
use App\Models\ParentGuardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    /**
     * User Login
     * POST /api/v1/auth/login
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'device_name' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = Auth::user();
            
            // Check if user is active
            if ($user->status !== 'active') {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Account is not active. Please contact support.'
                ], 403);
            }

            // Create token
            $token = $user->createToken($request->device_name ?? 'mobile-app')->plainTextToken;

            // Load user relationships with business context
            $user->load(['business', 'role', 'branch']);

            // Ensure user has a business association
            if (!$user->business) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any business. Please contact support.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'status' => $user->status,
                        'business_id' => $user->business_id,
                        'business' => [
                            'id' => $user->business->id,
                            'uuid' => $user->business->uuid,
                            'name' => $user->business->name,
                            'email' => $user->business->email,
                            'phone' => $user->business->phone,
                            'address' => $user->business->address,
                            'city' => $user->business->city,
                            'country' => $user->business->country,
                            'logo' => $user->business->logo,
                            'type' => $user->business->type,
                            'mode' => $user->business->mode,
                            'enabled_features' => $user->business->enabled_feature_ids,
                        ],
                        'role' => $user->role ? [
                            'id' => $user->role->id,
                            'name' => $user->role->name,
                            'permissions' => json_decode($user->role->permissions, true),
                        ] : null,
                        'branch' => $user->branch ? [
                            'id' => $user->branch->id,
                            'name' => $user->branch->name,
                            'code' => $user->branch->code,
                        ] : null,
                        'user_type' => $this->getUserType($user),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Login API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login'
            ], 500);
        }
    }

    /**
     * User Logout
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout'
            ], 500);
        }
    }

    /**
     * Get User Profile
     * GET /api/v1/auth/profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            $user->load(['business', 'role', 'branch']);

            // Ensure user has a business association
            if (!$user->business) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any business. Please contact support.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'status' => $user->status,
                        'business_id' => $user->business_id,
                        'business' => [
                            'id' => $user->business->id,
                            'uuid' => $user->business->uuid,
                            'name' => $user->business->name,
                            'email' => $user->business->email,
                            'phone' => $user->business->phone,
                            'address' => $user->business->address,
                            'city' => $user->business->city,
                            'country' => $user->business->country,
                            'logo' => $user->business->logo,
                            'type' => $user->business->type,
                            'mode' => $user->business->mode,
                            'enabled_features' => $user->business->enabled_feature_ids,
                        ],
                        'role' => $user->role ? [
                            'id' => $user->role->id,
                            'name' => $user->role->name,
                            'permissions' => json_decode($user->role->permissions, true),
                        ] : null,
                        'branch' => $user->branch ? [
                            'id' => $user->branch->id,
                            'name' => $user->branch->name,
                            'code' => $user->branch->code,
                        ] : null,
                        'user_type' => $this->getUserType($user),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Profile API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching profile'
            ], 500);
        }
    }

    /**
     * Update User Profile
     * PUT /api/v1/auth/profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'phone' => 'sometimes|required|string|max:20',
                'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update($request->only(['name', 'phone', 'email']));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'status' => $user->status,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update Profile API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating profile'
            ], 500);
        }
    }

    /**
     * Change Password
     * POST /api/v1/auth/change-password
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Change Password API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while changing password'
            ], 500);
        }
    }

    /**
     * Forgot Password
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link sent to your email'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to send password reset link'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Forgot Password API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing forgot password'
            ], 500);
        }
    }

    /**
     * Reset Password
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset successfully'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Reset Password API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resetting password'
            ], 500);
        }
    }

    /**
     * Parent/Guardian Login
     * POST /api/v1/auth/parent-login
     */
    public function parentLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
                'device_name' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $parent = ParentGuardian::where('email', $request->email)->first();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent/Guardian not found'
                ], 404);
            }

            // Check if parent has a password set
            if (!$parent->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'No password set. Please contact your school administrator.'
                ], 400);
            }

            // Check password
            if (!Hash::check($request->password, $parent->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if parent is active
            if ($parent->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is not active. Please contact support.'
                ], 403);
            }

            // Create token for parent
            $token = $parent->createToken($request->device_name ?? 'mobile-app')->plainTextToken;

            // Load parent relationships
            $parent->load(['business', 'students']);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'parent' => [
                        'id' => $parent->id,
                        'first_name' => $parent->first_name,
                        'last_name' => $parent->last_name,
                        'full_name' => $parent->full_name,
                        'email' => $parent->email,
                        'phone' => $parent->phone,
                        'relationship' => $parent->relationship,
                        'status' => $parent->status,
                        'business' => $parent->business ? [
                            'id' => $parent->business->id,
                            'name' => $parent->business->name,
                            'email' => $parent->business->email,
                            'phone' => $parent->business->phone,
                            'address' => $parent->business->address,
                            'city' => $parent->business->city,
                            'country' => $parent->business->country,
                        ] : null,
                        'students' => $parent->students->map(function ($student) {
                            return [
                                'id' => $student->id,
                                'first_name' => $student->first_name,
                                'last_name' => $student->last_name,
                                'full_name' => $student->full_name,
                                'student_id' => $student->student_id,
                                'class' => $student->class,
                                'status' => $student->status,
                            ];
                        }),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Parent Login API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during parent login'
            ], 500);
        }
    }

    /**
     * Refresh Token
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            
            // Delete current token
            $request->user()->currentAccessToken()->delete();
            
            // Create new token
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Refresh Token API Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while refreshing token'
            ], 500);
        }
    }

    /**
     * Get user type based on business and role
     */
    private function getUserType($user)
    {
        if ($user->isAdmin()) {
            return 'super_admin';
        } elseif ($user->isBusinessAdmin()) {
            return 'business_admin';
        } elseif ($user->isStaff()) {
            return 'staff';
        } else {
            return 'user';
        }
    }
}