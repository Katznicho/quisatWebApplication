<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        #fecth all users
        $users = User::all();
        // Pass businesses to populate select dropdown (optional: only if admin)
        $businesses = Business::all();

        return view('users.index', compact('users', 'businesses'));
    }



    public function update(Request $request, User $user)
    {
        $request->validate([
            'balance' => 'required|numeric',
            'profit' => 'required|numeric',
            'total_trades' => 'required|integer',
        ]);

        $user->update([
            'balance' => $request->balance,
            'profit' => $request->profit,
            'total_trades' => $request->total_trades,
        ]);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'status' => 'required|in:active,inactive,suspended',
            'business_id' => 'required|exists:businesses,id',
            //branch_id is optional, can be null
            'branch_id' => 'required|exists:branches,id',
            'profile_photo_path' => 'nullable|image|max:2048',
        ]);

        try {
            // Upload profile photo if provided
            if ($request->hasFile('profile_photo_path')) {
                $path = $request->file('profile_photo_path')->store('profile_photos', 'public');
                $validated['profile_photo_path'] = $path;
            }

            // Create the user WITHOUT a password
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'status' => $validated['status'],
                'business_id' => $validated['business_id'],
                'branch_id' => $validated['branch_id'],
                'profile_photo_path' => $validated['profile_photo_path'] ?? null,
                'password' => '', // Empty password
            ]);

            // Send password setup link (uses Laravel’s password reset logic)
            Password::sendResetLink(['email' => $user->email]);

            return redirect()->back()->with('success', 'User created successfully. A password setup link has been sent to their email.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     */
    // public function show(string $id)
    // {
    //     //
    //     return view('users.show');
    // }

    public function show(User $user)
{
    // Works automatically thanks to route model binding on slug
    return view('users.show', compact('user'));
}


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        return view('users.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
