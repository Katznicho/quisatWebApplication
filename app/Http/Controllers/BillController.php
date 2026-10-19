<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;

class BillController extends Controller
{
    /**
     * Display a listing of bills.
     */
    public function index(Request $request)
    {
        $query = Bill::query()->with('business')->latest();

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by bill type
        if ($request->has('bill_type') && $request->bill_type) {
            $query->where('bill_type', $request->bill_type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_reference', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $bills = $query->paginate(20);

        return view('bills.index', [
            'bills' => $bills,
            'filters' => $request->only(['status', 'bill_type', 'search']),
        ]);
    }

    /**
     * Display the specified bill.
     */
    public function show(Bill $bill)
    {
        $bill->load('business', 'customer');
        return view('bills.show', [
            'bill' => $bill,
        ]);
    }
}
