<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesPushAdmin;
use App\Jobs\SendPushBroadcastJob;
use App\Models\Business;
use App\Models\PushBroadcast;
use App\Services\PushConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PushBroadcastController extends Controller
{
    use AuthorizesPushAdmin;

    public function index(PushConfigurationService $config): View
    {
        $this->authorizePushAdmin();

        $query = PushBroadcast::query()->with('creator')->latest();

        if (! $this->isSuperAdmin()) {
            $query->where('business_id', Auth::user()->business_id);
        }

        $broadcasts = $query->paginate(20);
        $deviceStats = $config->deviceStats(fn () => $this->deviceTokensQuery());

        return view('push-notifications.index', [
            'broadcasts' => $broadcasts,
            'deviceStats' => $deviceStats,
            'configChecks' => $this->isSuperAdmin() ? $config->checks() : [],
            'isSuperAdmin' => $this->isSuperAdmin(),
        ]);
    }

    public function create(): View
    {
        $this->authorizePushAdmin();

        $businesses = $this->isSuperAdmin()
            ? Business::query()->where('id', '!=', 1)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('push-notifications.create', compact('businesses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePushAdmin();

        $validated = $this->validateBroadcast($request);

        $broadcast = PushBroadcast::create([
            ...$validated,
            'status' => PushBroadcast::STATUS_QUEUED,
            'created_by' => Auth::id(),
        ]);

        SendPushBroadcastJob::dispatchSync($broadcast);

        return redirect()
            ->route('push-notifications.show', $broadcast)
            ->with('success', 'Notification sent.');
    }

    public function show(PushBroadcast $pushNotification, PushConfigurationService $config): View
    {
        $this->authorizePushAdmin();
        $this->authorizeBroadcast($pushNotification);

        $pushNotification->load('creator', 'business');

        $deviceStats = $config->deviceStats(fn () => $this->deviceTokensQuery()->where('is_active', true));

        return view('push-notifications.show', [
            'broadcast' => $pushNotification,
            'deviceStats' => $deviceStats,
        ]);
    }

    protected function validateBroadcast(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:1000',
            'image' => 'nullable|image|max:5120',
            'audience' => 'required|in:all,parents,staff,business',
            'business_id' => 'nullable|exists:businesses,id',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:push,in_app',
            'deep_link' => 'nullable|string|max:255',
        ]);

        $businessId = $validated['business_id'] ?? null;

        if (! $this->isSuperAdmin()) {
            $businessId = Auth::user()->business_id;
            $validated['audience'] = in_array($validated['audience'], ['parents', 'staff', 'business'], true)
                ? $validated['audience']
                : 'business';
        }

        if ($validated['audience'] === PushBroadcast::AUDIENCE_BUSINESS && ! $businessId) {
            $businessId = $this->isSuperAdmin() ? null : Auth::user()->business_id;
        }

        $data = null;
        if (! empty($validated['deep_link'])) {
            $data = ['url' => $validated['deep_link']];
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('push-notifications', 'public');
        }

        return [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'image_path' => $imagePath,
            'audience' => $validated['audience'],
            'business_id' => $businessId,
            'channels' => array_values(array_unique($validated['channels'])),
            'data' => $data,
        ];
    }

    protected function authorizeBroadcast(PushBroadcast $broadcast): void
    {
        if ($this->isSuperAdmin()) {
            return;
        }

        if ((int) $broadcast->business_id !== (int) Auth::user()->business_id) {
            abort(403);
        }
    }
}
