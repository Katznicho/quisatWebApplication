<?php

namespace App\Http\Controllers;

use App\Models\MarzSmsTopup;
use App\Models\SmsMessage;
use App\Services\MarzSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarzSmsController extends Controller
{
    public function index(MarzSmsService $marzSms)
    {
        $settings = $marzSms->settings();
        $balance = $settings->hasCredentials() ? $marzSms->balance($settings) : null;

        $stats = [
            'sent_today' => SmsMessage::query()->where('status', 'sent')->whereDate('sent_at', today())->count(),
            'failed_today' => SmsMessage::query()->whereIn('status', ['failed', 'skipped'])->whereDate('created_at', today())->count(),
            'sent_total' => SmsMessage::query()->where('status', 'sent')->count(),
            'spent_this_month' => (float) SmsMessage::query()
                ->where('status', 'sent')
                ->where('sent_at', '>=', now()->startOfMonth())
                ->sum('cost'),
        ];

        $topups = MarzSmsTopup::query()->with('initiator')->latest()->limit(10)->get();

        return view('marzsms.index', compact('settings', 'balance', 'stats', 'topups'));
    }

    public function updateSettings(Request $request, MarzSmsService $marzSms)
    {
        $validated = $request->validate([
            'base_url' => 'required|url|max:255',
            'api_key' => 'nullable|string|max:255',
            'api_secret' => 'nullable|string|max:255',
            'low_balance_threshold' => 'required|integer|min:0',
            'is_enabled' => 'nullable|boolean',
        ]);

        $settings = $marzSms->settings();

        $settings->fill([
            'base_url' => rtrim($validated['base_url'], '/'),
            'low_balance_threshold' => $validated['low_balance_threshold'],
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        // Blank key/secret fields keep the stored credentials.
        if (filled($validated['api_key'] ?? null)) {
            $settings->api_key = trim($validated['api_key']);
        }

        if (filled($validated['api_secret'] ?? null)) {
            $settings->api_secret = trim($validated['api_secret']);
        }

        if ($settings->is_enabled && ! $settings->hasCredentials()) {
            return back()->withInput()->withErrors([
                'api_key' => 'Enter the API key and secret before enabling MarzSMS.',
            ]);
        }

        $settings->save();

        $check = $marzSms->balance($settings);

        if (! $check['success']) {
            return redirect()
                ->route('marzsms.index')
                ->with('warning', 'Settings saved, but the connection check failed: '.$check['message']);
        }

        return redirect()
            ->route('marzsms.index')
            ->with('success', 'MarzSMS settings saved and connection verified.');
    }

    public function topup(Request $request, MarzSmsService $marzSms)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500|max:10000000',
            'phone_number' => 'required|string|max:20',
        ]);

        $result = $marzSms->initiateTopup((float) $validated['amount'], $validated['phone_number'], Auth::id());

        return redirect()
            ->route('marzsms.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function syncTopup(MarzSmsTopup $topup, MarzSmsService $marzSms)
    {
        $result = $marzSms->syncTopup($topup);

        return redirect()
            ->route('marzsms.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function sendTest(Request $request, MarzSmsService $marzSms)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:20',
            'message' => 'required|string|max:'.MarzSmsService::MAX_LENGTH,
        ]);

        if (! $marzSms->isReady()) {
            return back()->withInput()->with('error', 'Enable MarzSMS and save valid credentials first.');
        }

        $sms = $marzSms->sendNow($validated['phone_number'], $validated['message'], 'test', null, null, Auth::id());

        if (! $sms) {
            return back()->withInput()->with('error', 'Enter a valid Ugandan phone number (e.g. 0771234567 or +256771234567).');
        }

        return redirect()
            ->route('marzsms.index')
            ->with(
                $sms->status === 'sent' ? 'success' : 'error',
                $sms->status === 'sent' ? "Test SMS sent to {$sms->recipient}." : 'Test SMS failed: '.$sms->error
            );
    }
}
