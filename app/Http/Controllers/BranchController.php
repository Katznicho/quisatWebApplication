<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Business;

class BranchController extends Controller
{
    /**
     * Display a listing of the branches for the current user's business.
     */
    public function index()
    {
        try {
            $business = Business::all(); 
            // $branches = Branch::where('business_id', Auth::user()->business_id)->get();
            return view('branches.index', compact('business'));
        } catch (\Exception $e) {
            Log::error('Branch index error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load branches.');
        }
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create()
    {
        return view('branches.create');
    }

    /**
     * Store a newly created branch in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'business_id' => 'required|exists:businesses,id', // Uncomment if you want to allow branch creation for specific businesses
        ]);

        try {
            Branch::create([
                'uuid'        => Str::uuid(),
                'business_id' => $request->business_id,
                'name'        => $request->name,
                'email'       => $request->email,
                'phone'       => $request->phone,
                'address'     => $request->address,
            ]);

            return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
        } catch (\Exception $e) {
            Log::error('Branch store error: ' . $e->getMessage());
            return back()->with('error', 'Failed to create branch.')->withInput();
        }
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch)
    {
        try {
            if ($branch->business_id !== Auth::user()->business_id) {
                return abort(403);
            }

            return view('branches.show', compact('branch'));
        } catch (\Exception $e) {
            Log::error('Branch show error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load branch.');
        }
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch)
    {
        try {
            if ($branch->business_id !== Auth::user()->business_id) {
                return abort(403);
            }

            return view('branches.edit', compact('branch'));
        } catch (\Exception $e) {
            Log::error('Branch edit error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load edit form.');
        }
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        try {
            if ($branch->business_id !== Auth::user()->business_id) {
                return abort(403);
            }

            $branch->update([
                'name'    => $request->name,
                'email'   => $request->email,
                'phone'   => $request->phone,
                'address' => $request->address,
            ]);

            return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
        } catch (\Exception $e) {
            Log::error('Branch update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update branch.')->withInput();
        }
    }

    /**
     * Remove the specified branch from storage.
     */
    public function destroy(Branch $branch)
    {
        try {
            if ($branch->business_id !== Auth::user()->business_id) {
                return abort(403);
            }

            $branch->delete();

            return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Branch destroy error: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete branch.');
        }
    }
}
