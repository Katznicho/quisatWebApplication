<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;


class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view("collections.index");
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
        try {
            // Validate fields
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'amount' => 'required|numeric|min:100', // You can adjust min value
                'phone_number' => [
                    'required',
                    'regex:/^256[0-9]{9}$/'
                ],
                'method' => 'required|in:mobile_money,card,bank_transfer,crypto',
                'description' => 'nullable|string|max:1000',
            ]);

            // Determine provider
            $prefix = (int) substr($validated['phone_number'], 3, 3);
            if ($prefix >= 700 && $prefix <= 759) {
                $provider = 'airtel';
            } elseif ($prefix >= 760 && $prefix <= 789) {
                $provider = 'mtn';
            } else {
                return back()->withErrors(['phone_number' => 'Only MTN and Airtel numbers are supported.']);
            }

            // You can hardcode or pull the business depending on your logic
            $business = auth()->user()->business ?? Business::first(); // adjust as needed

            if (!$business) {
                return back()->withErrors(['error' => 'Business not found.']);
            }

            // Calculate transaction charges
            $chargePercentage = $business->percentage_charge ?? 0;
            $chargeAmount = round(($chargePercentage / 100) * $validated['amount']);

            // Generate reference
            $reference = '25' . now()->format('YmdHis') . rand(1000, 9999);

            // Store main transaction
            Transaction::create([
                'business_id' => $business->id,
                'reference' => $reference,
                'transaction_for' => 'main',
                'amount' => $validated['amount'],
                'description' => $validated['title'],
                'status' => 'pending',
                'type' => 'credit',
                'origin' => 'web',
                'phone_number' => $validated['phone_number'],
                'provider' => $provider,
                'service' => 'collection',
                'currency' => 'UGX',
                'method' => $validated['method'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Store charge transaction
            if ($chargeAmount > 0) {
                Transaction::create([
                    'business_id' => $business->id,
                    'reference' => $reference,
                    'transaction_for' => 'charge',
                    'amount' => $chargeAmount,
                    'description' => 'Charge for ' . $validated['title'],
                    'status' => 'pending',
                    'type' => 'debit',
                    'origin' => 'web',
                    'phone_number' => $validated['phone_number'],
                    'provider' => $provider,
                    'service' => 'collection',
                    'currency' => 'UGX',
                    'method' => $validated['method'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            // TODO: Dispatch collection job to actual API (e.g., Ssentezo Wallet)
            // dispatch(new ProcessCollection($reference)); // optional

            return back()->with('success', 'Collection initialized successfully. Reference: ' . $reference);
        } catch (\Throwable $e) {
            // Remove or log in production
            dd($e);

            Log::error('Collection Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'An error occurred. Please try again.']);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
