<?php

namespace App\Http\Controllers;

use App\Models\DataFeed;
use App\Models\FundRaiser;
use App\Models\Room;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // public function index()
    // {


    //     $user = Auth::user();
    //     $business = $user->business; // Make sure 'business' relationship exists
    //     $branch = $user->branch;

    //    //select  rooms that belong to this branch
    //    $rooms = Room::where('branch_id', $branch->id)->get();
    //     return view('pages/dashboard/dashboard', compact('business', 'branch', 'rooms'));
    // }

    public function index(Request $request)
    {
        $user = Auth::user();
        $business = $user->business;
        $branch = $user->branch;
        $rooms = Room::where('branch_id', $branch->id)->get();

        // Handle POST to set room_id in session
        if ($request->isMethod('post')) {
            $request->validate([
                'room_id' => 'required|exists:rooms,id',
            ]);
            session(['room_id' => $request->room_id]);
            return redirect()->route('dashboard'); // or redirect back to same page to avoid resubmission
        }

        return view('pages/dashboard/dashboard', compact('business', 'branch', 'rooms'));
    }

    /**
     * Displays the analytics screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function analytics()
    {
        return view('pages/dashboard/analytics');
    }

    /**
     * Displays the fintech screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function fintech()
    {
        return view('pages/dashboard/fintech');
    }
}
