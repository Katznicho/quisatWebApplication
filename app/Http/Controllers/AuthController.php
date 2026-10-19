<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use SoapClient;
use stdClass;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (request()->hasAny(['password', 'username', 'locationcode'])) {
            Log::warning('Login page accessed with credentials in query string', [
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
            ]);

            return redirect()->route('login');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => __('The provided credentials do not match our records.'),
        ]);
    }

    /**
     * Customer Portal style login using Account ID, Username and Password.
     * Authenticates against the external SOAP service, stores session data,
     * and logs the user into this Laravel app for dashboard access.
     */
    public function portalLogin(Request $request)
    {
        try {
            if (!$request->isMethod('post')) {
                Log::warning('Portal login attempt via non-POST method', [
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);

                return response()->json([
                    'response' => 'failure',
                    'message' => 'Login must be submitted via POST request.',
                ], 405);
            }

            if ($request->query('password') || $request->query('username') || $request->query('locationcode')) {
                Log::warning('Portal login attempt with credentials in query string', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);

                return response()->json([
                    'response' => 'failure',
                    'message' => 'Credentials cannot be sent via URL parameters for security reasons. Please use the login form.',
                ], 400);
            }

            $request->validate([
                'username' => 'required',
                'password' => 'required',
                'locationcode' => 'required',
            ]);

            $webServiceUrl = config('app.webService');
            $soapUsername = config('app.soapUsername');
            $soapPassword = config('app.soapPassword');

            if (!$webServiceUrl || !$soapUsername || !$soapPassword) {
                Log::error('SOAP configuration missing for portal login');

                return response()->json([
                    'response' => 'failure',
                    'message' => 'Login service is not configured. Please contact the administrator.',
                ], 500);
            }

            $options = [
                'login' => $soapUsername,
                'password' => $soapPassword,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $params = new stdClass();
            $params->customerNo = trim($request->locationcode);
            $params->userName = trim($request->username);
            $params->password = trim($request->password);

            $res = $service->AuthenticateUser($params);

            $returnValue = $res->return_value ?? null;

            // Log raw AuthenticateUser response (without credentials)
            Log::info('Portal AuthenticateUser response', [
                'customerNo' => $params->customerNo,
                'userName' => $params->userName,
                'raw_return_value' => $returnValue,
                'return_value_type' => gettype($returnValue),
            ]);

            // Treat false, null, empty string, or string "false" as auth failure
            $authSuccess = $returnValue !== null
                && $returnValue !== false
                && $returnValue !== ''
                && strtolower((string) $returnValue) !== 'false';

            if (! $authSuccess) {
                $message = 'Invalid username, password, or account ID.';
                if (is_string($returnValue) && trim($returnValue) !== '' && strtolower($returnValue) !== 'false') {
                    $message = trim($returnValue);
                }

                return response()->json([
                    'response' => 'failure',
                    'message' => $message,
                ]);
            }

            $userParams = new stdClass();
            $userParams->customerNo = trim($request->locationcode);
            $userParams->userName = trim($request->username);
            $profileRes = $service->UserProfile($userParams);

            // Log raw UserProfile response
            Log::info('Portal UserProfile raw response', [
                'customerNo' => $userParams->customerNo,
                'userName' => $userParams->userName,
                'raw_return_value' => $profileRes->return_value ?? null,
            ]);

            $decodedData = json_decode($profileRes->return_value ?? '{}', true) ?: [];

            // Log decoded profile data (excluding image blob)
            $logProfile = $decodedData;
            unset($logProfile['image']);
            Log::info('Portal UserProfile decoded data', [
                'profile' => $logProfile,
            ]);

            $imagePath = null;
            if (!empty($decodedData['image']) && str_contains($decodedData['image'], 'base64,')) {
                $base64String = preg_replace('#^data:image/\w+;base64,#i', '', $decodedData['image']);
                $imageData = base64_decode($base64String);

                if ($imageData !== false) {
                    $filename = 'user_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->username) . '_' . time() . '.jpg';
                    Storage::disk('public')->put('avatars/' . $filename, $imageData);
                    $imagePath = 'avatars/' . $filename;
                } else {
                    Log::error('Failed to decode base64 image for user: ' . $request->username);
                }
            }

            session([
                'userName' => $request->username,
                'fullName' => trim($decodedData['fullName'] ?? $request->username),
                'phoneNo' => $decodedData['phoneNo'] ?? null,
                'email' => $decodedData['email'] ?? null,
                'phoneNumber' => $decodedData['phoneNumber'] ?? null,
                'customerNumber' => $request->locationcode,
                'accountType' => $decodedData['accountType'] ?? null,
                'assignedCards' => $decodedData['assignedCards'] ?? null,
                'picture' => $imagePath,
                'customerNo' => $decodedData['customerNo'] ?? null,
                'role' => $decodedData['role'] ?? null,
                'permissions' => $decodedData['permissions'] ?? null,
                'portal_authenticated' => true,
            ]);

            return response()->json([
                'response' => 'success',
                'message' => 'Login successful.',
            ]);
        } catch (Exception $ex) {
            Log::error('Portal login error', [
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return response()->json([
                'response' => 'failure',
                'message' => $ex->getMessage() ?: 'Unable to login at the moment. Please try again later.',
                'exception' => get_class($ex),
            ], 500);
        }
    }


    public function dashboard()
    {
        try {
            $webServiceUrl = config('app.webService');
            $soapUsername = config('app.soapUsername');
            $soapPassword = config('app.soapPassword');

            if (! $webServiceUrl || ! $soapUsername || ! $soapPassword) {
                Log::error('Dashboard SOAP configuration missing');

                $response = (object) [];

                return view('dashboard', compact('response'));
            }

            $options = [
                'login' => $soapUsername,
                'password' => $soapPassword,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $params = new stdClass();
            $params->userName = trim(session('userName'));
            $params->customerNo = trim(session('customerNumber'));

            $res = $service->UserDashboard($params);

            Log::info('Dashboard SOAP Raw Response', [
                'raw_response' => $res,
                'return_value' => $res->return_value ?? 'No return_value',
            ]);

            $response = json_decode($res->return_value ?? '{}');

            if (! is_object($response)) {
                $response = new stdClass();
            }

            if (isset($response->transactions) && is_array($response->transactions)) {
                $response->transactions = array_map(function ($transaction) {
                    if (is_array($transaction)) {
                        return (object) $transaction;
                    }

                    return $transaction;
                }, $response->transactions);
            } elseif (! isset($response->transactions)) {
                $response->transactions = [];
            }

            session([
                'dashboard_stations' => $response->stations ?? 0,
                'dashboard_customerNo' => $response->customerNo ?? null,
                'dashboard_customerName' => $response->customerName ?? null,
                'dashboard_accounts' => $response->accounts ?? 0,
                'dashboard_activeAccounts' => $response->activeAccounts ?? 0,
                'dashboard_blockedAccounts' => $response->blockedAccounts ?? 0,
                'dashboard_drivers' => is_numeric($response->drivers ?? 0) ? (int) ($response->drivers) : 0,
                'dashboard_regions' => is_numeric($response->regions ?? 0) ? (int) ($response->regions) : 0,
            ]);

            return view('dashboard', compact('response'));
        } catch (\Throwable $th) {
            Log::error('Dashboard error', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            $response = (object) [];

            return view('dashboard', compact('response'));
        }
    }

    public function profile()
    {
        return view('profile');
    }

    /**
     * Show forgot password form (Account ID + Username → send OTP).
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send OTP via SOAP SendOTP for password reset.
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'customerNo' => 'required|string',
            ]);

            $webServiceUrl = config('app.webService');
            $soapUsername = config('app.soapUsername');
            $soapPassword = config('app.soapPassword');

            if (! $webServiceUrl || ! $soapUsername || ! $soapPassword) {
                return response()->json([
                    'response' => 'failure',
                    'message' => 'Password reset service is not configured.',
                ], 500);
            }

            $options = [
                'login' => $soapUsername,
                'password' => $soapPassword,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $params = new stdClass();
            $params->customerNo = trim($request->customerNo);
            $params->userName = trim($request->username);

            $res = $service->SendOTP($params);
            $returnValue = $res->return_value ?? false;

            if ($returnValue) {
                return response()->json([
                    'response' => 'success',
                    'message' => 'Password reset OTP sent to your email.',
                    'username' => $request->username,
                    'customerNo' => $request->customerNo,
                ]);
            }

            return response()->json([
                'response' => 'failure',
                'message' => 'Invalid Account ID or Username.',
            ]);
        } catch (Exception $ex) {
            Log::error('Forgot password error', ['message' => $ex->getMessage()]);

            return response()->json([
                'response' => 'failure',
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP via SOAP VerifyOTP.
     */
    public function verifyOTP(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'customerNo' => 'required|string',
                'otp' => 'required|string',
            ]);

            $webServiceUrl = config('app.webService');
            $soapUsername = config('app.soapUsername');
            $soapPassword = config('app.soapPassword');

            if (! $webServiceUrl || ! $soapUsername || ! $soapPassword) {
                return response()->json([
                    'response' => 'failure',
                    'message' => 'OTP verification service is not configured.',
                ], 500);
            }

            $options = [
                'login' => $soapUsername,
                'password' => $soapPassword,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $params = new stdClass();
            $params->customerNo = trim($request->customerNo);
            $params->userName = trim($request->username);
            $params->oTP = trim($request->otp);

            $res = $service->VerifyOTP($params);
            $returnValue = $res->return_value ?? false;

            if ($returnValue) {
                return response()->json([
                    'response' => 'success',
                    'message' => 'OTP verified successfully.',
                    'username' => $request->username,
                    'customerNo' => $request->customerNo,
                ]);
            }

            return response()->json([
                'response' => 'failure',
                'message' => 'Invalid or expired OTP.',
            ]);
        } catch (Exception $ex) {
            Log::error('Verify OTP error', ['message' => $ex->getMessage()]);

            return response()->json([
                'response' => 'failure',
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    /**
     * Change portal user password via SOAP SetUserPassword (reset flow or profile).
     */
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'customerNo' => 'required|string',
                'password' => 'required|string|confirmed|min:8|max:16',
            ]);

            $webServiceUrl = config('app.webService');
            $soapUsername = config('app.soapUsername');
            $soapPassword = config('app.soapPassword');

            if (! $webServiceUrl || ! $soapUsername || ! $soapPassword) {
                return response()->json([
                    'response' => 'failure',
                    'message' => 'Password service is not configured.',
                ], 500);
            }

            $options = [
                'login' => $soapUsername,
                'password' => $soapPassword,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];

            $service = new SoapClient($webServiceUrl, $options);

            $params = new stdClass();
            $params->customerNo = trim($request->customerNo);
            $params->userName = trim($request->username);
            $params->newPassword = trim($request->password);

            $res = $service->SetUserPassword($params);
            $returnValue = $res->return_value ?? false;

            if ($returnValue) {
                return response()->json([
                    'response' => 'success',
                    'message' => 'Password changed successfully.',
                    'username' => $request->username,
                    'customerNo' => $request->customerNo,
                ]);
            }

            return response()->json([
                'response' => 'failure',
                'message' => 'Failed to change password.',
            ]);
        } catch (Exception $ex) {
            Log::error('Change password error', ['message' => $ex->getMessage()]);

            return response()->json([
                'response' => 'failure',
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function showResetPassword(Request $request, $token = null)
    {
        if (!$token) {
            $token = $request->route('token');
        }
        
        $email = $request->query('email') ?? $request->input('email');
        
        if (!$email || !$token) {
            return redirect()->route('forgot-password')
                ->withErrors(['email' => 'Invalid password reset link.']);
        }
        
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',      // Must contain at least one lowercase letter
                'regex:/[A-Z]/',      // Must contain at least one uppercase letter
                'regex:/[0-9]/',      // Must contain at least one digit
                'regex:/[@$!%*#?&]/', // Must contain at least one special character
            ],
        ], [
            'email.exists' => 'We could not find a user with that email address.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*#?&).',
            'password.min' => 'Password must be at least 8 characters long.',
        ]);

        // Check if token is valid
        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetToken) {
            return back()->withErrors(['email' => 'Invalid or expired reset token.']);
        }

        // Check if token matches (hash comparison)
        if (!Hash::check($request->token, $resetToken->token)) {
            return back()->withErrors(['email' => 'Invalid or expired reset token.']);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($resetToken->created_at) > 60) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();
            return back()->withErrors(['email' => 'Reset token has expired. Please request a new one.']);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete used token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return redirect()->route('login')->with('status', 'Your password has been reset! You can now login with your new password.');
    }
}