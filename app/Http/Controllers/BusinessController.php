<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('businesses.index');
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
            'email' => 'required|email|unique:businesses,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);

        try {
            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
                $validated['logo'] = $logoPath;
            } else {
                $validated['logo'] = null;
            }

            // Generate time-based account number with prefix '25' and random 2-digit suffix
            $validated['account_number'] = 'KS' . time();

            //dd($validated); // For debugging purposes, remove in production

            // Create business
            Business::create($validated);

            return redirect()->back()->with('success', 'Business created successfully!');

        // } catch (\Illuminate\Database\QueryException $e) {
            // if ($e->getCode() == 23000) { // Unique constraint violation
            //     return redirect()->back()->with('error', 'Account number already exists. Please try again.');
            // }

            // Log::error('DB error while creating business: ' . $e->getMessage());
            // return redirect()->back()->with('error', 'A database error occurred. Please contact support.');
        } catch (\Exception $e) {
            Log::error('General error while creating business: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again.');
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(Business $business)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Business $business)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Business $business)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Business $business)
    {
        //
    }
}
