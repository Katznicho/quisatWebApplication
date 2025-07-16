<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AirtelController extends Controller
{
    public function airtelCallback(Request $request)
    {
        try {
            // Log the full request payload
            Log::info('Airtel Callback Received:', $request->all());

            // Perform any necessary processing here...

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Airtel Callback Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['status' => 'error', 'message' => 'Something went wrong.'], 500);
        }
    }
}
