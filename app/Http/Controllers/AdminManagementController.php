<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display admin dashboard with user management
     */
    public function index()
    {
        // Get all users with their relationships
        $users = User::with(['business', 'role', 'branch'])->get();
        
        // Filter users by type
        $admins = $users->filter(function ($user) {
            return $user->isAdmin();
        });
        
        $businessAdmins = $users->filter(function ($user) {
            return $user->isBusinessAdmin();
        });
        
        $staff = $users->filter(function ($user) {
            return $user->isStaff();
        });

        $businesses = Business::all();
        $branches = Branch::all();
        $roles = Role::all();

        return view('admin.dashboard', compact('admins', 'businessAdmins', 'staff', 'businesses', 'branches', 'roles'));
    }

    /**
     * Show the form for creating a new admin
     */
    public function createAdmin()
    {
        $businesses = Business::all();
        $branches = Branch::all();
        $roles = Role::all();

        return view('admin.create-admin', compact('businesses', 'branches', 'roles'));
    }

    /**
     * Store a newly created admin
     */
    public function storeAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'business_id' => 'required|exists:businesses,id',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        try {
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(12)), // Temporary password
                'business_id' => $request->business_id,
                'role_id' => $request->role_id,
                'branch_id' => $request->branch_id,
                'status' => $request->status,
            ]);

            // Send password reset email
            Password::sendResetLink(['email' => $user->email]);

            return redirect()->route('admin.dashboard')->with('success', 'Admin created successfully. Password reset link sent to email.');
        } catch (\Exception $e) {
            Log::error('Error creating admin: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create admin: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new staff member
     */
    public function createStaff()
    {
        $businesses = Business::all();
        $branches = Branch::all();
        $roles = Role::where('name', 'Staff')->get();

        return view('admin.create-staff', compact('businesses', 'branches', 'roles'));
    }

    /**
     * Store a newly created staff member
     */
    public function storeStaff(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'business_id' => 'required|exists:businesses,id',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        try {
            // Get or create Staff role
            $staffRole = Role::firstOrCreate(
                ['name' => 'Staff'],
                [
                    'business_id' => $request->business_id,
                    'description' => 'Staff member with limited permissions',
                    'permissions' => ['view_reports', 'manage_own_profile']
                ]
            );

            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(12)), // Temporary password
                'business_id' => $request->business_id,
                'role_id' => $staffRole->id,
                'branch_id' => $request->branch_id,
                'status' => $request->status,
            ]);

            // Send password reset email
            Password::sendResetLink(['email' => $user->email]);

            return redirect()->route('admin.dashboard')->with('success', 'Staff member created successfully. Password reset link sent to email.');
        } catch (\Exception $e) {
            Log::error('Error creating staff: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create staff: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing a user
     */
    public function editUser(User $user)
    {
        $businesses = Business::all();
        $branches = Branch::all();
        $roles = Role::all();

        return view('admin.edit-user', compact('user', 'businesses', 'branches', 'roles'));
    }

    /**
     * Update the specified user
     */
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'business_id' => 'required|exists:businesses,id',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'business_id' => $request->business_id,
                'role_id' => $request->role_id,
                'branch_id' => $request->branch_id,
                'status' => $request->status,
            ]);

            return redirect()->route('admin.dashboard')->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user
     */
    public function destroyUser(User $user)
    {
        try {
            $user->delete();
            return redirect()->route('admin.dashboard')->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user)
    {
        try {
            Password::sendResetLink(['email' => $user->email]);
            return redirect()->back()->with('success', 'Password reset link sent to ' . $user->email);
        } catch (\Exception $e) {
            Log::error('Error sending password reset: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send password reset link: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        try {
            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
            $user->update(['status' => $newStatus]);

            return redirect()->back()->with('success', 'User status updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error toggling user status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update user status: ' . $e->getMessage());
        }
    }

    /**
     * Get users by business
     */
    public function getUsersByBusiness(Request $request)
    {
        $businessId = $request->business_id;
        $users = User::with(['business', 'role', 'branch'])
            ->where('business_id', $businessId)
            ->get();

        return response()->json($users);
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(Request $request)
    {
        $roleId = $request->role_id;
        $users = User::with(['business', 'role', 'branch'])
            ->where('role_id', $roleId)
            ->get();

        return response()->json($users);
    }
}
