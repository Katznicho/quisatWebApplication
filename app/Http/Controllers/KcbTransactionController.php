<?php

namespace App\Http\Controllers;

use App\Models\KcbTransaction;
use Illuminate\Http\Request;

class KcbTransactionController extends Controller
{
    /**
     * Display a listing of KCB transactions.
     */
    public function index(Request $request)
    {
        $query = KcbTransaction::query()->latest();

        // Filter by transaction type
        if ($request->has('type') && $request->type) {
            $query->where('transaction_type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status_code', $request->status);
        }

        // Search by customer reference or transaction reference
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_reference', 'like', "%{$search}%")
                  ->orWhere('transaction_reference', 'like', "%{$search}%")
                  ->orWhere('request_id', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(20);

        return view('kcb-transactions.index', [
            'transactions' => $transactions,
            'filters' => $request->only(['type', 'status', 'search']),
        ]);
    }

    /**
     * Display the specified KCB transaction.
     */
    public function show(KcbTransaction $kcbTransaction)
    {
        return view('kcb-transactions.show', [
            'transaction' => $kcbTransaction,
        ]);
    }
}
