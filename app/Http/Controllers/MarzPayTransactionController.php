<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class MarzPayTransactionController extends Controller
{
    public function index()
    {
        if (! Auth::check() || (int) Auth::user()->business_id !== 1) {
            abort(403, 'Only super administrators can view MarzPay transactions.');
        }

        return view('marzpay.transactions');
    }
}
