<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $query = Customer::query()->with('business')->latest();

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_reference', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(20);

        return view('customers.index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        $customer->load('business', 'bills');
        return view('customers.show', [
            'customer' => $customer,
        ]);
    }
}
