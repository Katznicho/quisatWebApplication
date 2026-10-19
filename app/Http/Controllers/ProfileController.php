<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;
use stdClass;

class ProfileController extends Controller
{
    public function updateProfilePicture(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $webServiceUrl = config('app.webService');
            $username = config('app.soapUsername');
            $password = config('app.soapPassword');

            if (! $webServiceUrl || ! $username || ! $password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile service is not configured.',
                ], 500);
            }

            $options = [
                'login' => $username,
                'password' => $password,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $imageBase64 = base64_encode($request->file('image')->get());
            $params = new stdClass();
            $params->userName = session('userName');
            $params->imageBase64 = $imageBase64;

            $result = $service->UpdateProfilePicture($params);
            $data = $result->return_value ?? null;

            if ($data) {
                $userParams = new stdClass();
                $userParams->userName = trim(session('userName'));
                $res = $service->UserProfile($userParams);
                $decodedData = json_decode($res->return_value ?? '{}', true) ?: [];

                session([
                    'fullName' => $decodedData['fullName'] ?? session('fullName'),
                    'phoneNo' => $decodedData['phoneNo'] ?? session('phoneNo'),
                    'email' => $decodedData['email'] ?? session('email'),
                    'picture' => $decodedData['image'] ?? session('picture'),
                    'customerNo' => $decodedData['customerNo'] ?? session('customerNo'),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile picture updated successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
