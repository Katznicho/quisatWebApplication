<?php

namespace App\Http\Controllers;

use App\Services\CustomerSoapClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpeCategoryController extends Controller
{
    public function index()
    {
        return view('mpe-categories.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $customerNo = trim((string) session('customerNumber'));

            if ($customerNo === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer number not found. Please log in again.',
                ], 403);
            }

            $soap = app(CustomerSoapClient::class);
            $result = $soap->call('CreateCustomerMPECategory', [
                'customerNo' => $customerNo,
                'createJson' => json_encode([
                    'code' => $validated['code'],
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? '',
                ]),
            ]);

            $success = $result !== false
                && $result !== null
                && strtolower((string) $result) !== 'false';

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => is_string($result) && $result !== '' ? $result : 'Failed to create category.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => is_string($result) && $result !== '' && strtolower($result) !== 'true'
                    ? $result
                    : 'MPE category created successfully.',
            ]);
        } catch (\Throwable $th) {
            Log::error('CreateCustomerMPECategory failed', [
                'error' => $th->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: '.$th->getMessage(),
            ], 500);
        }
    }
}
