<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use SoapClient;
use stdClass;

class StaffController extends Controller
{
    public function index()
    {
        return view('staff.index');
    }

    public function edit(string $username)
    {
        try {
            $docNo = session('customerNumber');
            $userName = $username;

            $url = config('app.odata') . "/CustomerAdministratorAPI?\$filter=Customer_No eq '" . $docNo . "' and User_Name eq '" . $userName . "'";

            $response = Http::withBasicAuth(
                config('app.soapUsername'),
                config('app.soapPassword')
            )->get($url);

            if (! $response->successful()) {
                return back()->with('error', 'Failed to fetch user details');
            }

            $data = json_decode($response->body());
            $user = $data->value[0] ?? null;

            if (! $user) {
                abort(404, 'User not found');
            }

            return view('staff.edit', compact('user'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function updateUserDetails(Request $request)
    {
        try {
            $request->validate([
                'patch_key' => 'required',
                'field_name' => 'required',
                'field_value' => 'required',
                'field_type' => 'required',
            ]);

            $key = $request->patch_key;
            $pathUrl = config('app.odata') . "/CustomerAdministratorAPI" . '(' . $key . ')';

            $data = [
                $request->field_name => $request->field_type === 'number'
                    ? (int) $request->field_value
                    : $request->field_value,
            ];

            $res = GeneralController::sendPatchRequest($pathUrl, $data);

            if ($res['status'] === 'success') {
                return response()->json(['success' => true, 'message' => 'The user has been updated successfully'], 200);
            }

            return response()->json(['success' => false, 'message' => $res['message']], 500);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }
}

