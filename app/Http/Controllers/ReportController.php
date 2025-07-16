<?php

namespace App\Http\Controllers;

use App\Models\UserSubscriptionPlan;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $userPlan = UserSubscriptionPlan::where('user_id', auth()->id())
            ->where('status', 'active')
            ->first();

        if (!$userPlan) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'You must have an active subscription to access this feature.');
        }
        return view('reports.index');
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
        //
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
