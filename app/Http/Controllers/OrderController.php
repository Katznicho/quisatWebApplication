<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of orders for the business (seller's orders)
     */
    public function index(Request $request)
    {
        $query = Order::where('business_id', Auth::user()->business_id)
            ->with(['items.product', 'customer'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15);

        // Statistics
        $stats = [
            'total' => Order::where('business_id', Auth::user()->business_id)->count(),
            'pending' => Order::where('business_id', Auth::user()->business_id)->where('status', 'pending')->count(),
            'confirmed' => Order::where('business_id', Auth::user()->business_id)->where('status', 'confirmed')->count(),
            'processing' => Order::where('business_id', Auth::user()->business_id)->where('status', 'processing')->count(),
            'shipped' => Order::where('business_id', Auth::user()->business_id)->where('status', 'shipped')->count(),
            'delivered' => Order::where('business_id', Auth::user()->business_id)->where('status', 'delivered')->count(),
        ];

        return view('orders.index', compact('orders', 'stats'));
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        if ($order->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $order->load(['items.product', 'customer', 'business']);
        return view('orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        if ($order->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'seller_notes' => 'nullable|string|max:1000',
        ]);

        $order->update($validated);

        return redirect()->back()
            ->with('success', 'Order status updated successfully!');
    }
}
