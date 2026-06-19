<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $business = $user->business;

        if ((int) $user->business_id !== 1 && (! $business || ! $business->hasFeatureByName('KidsMart'))) {
            abort(403, 'Kids Mart is not enabled for this business.');
        }

        return view('orders.index');
    }
}
