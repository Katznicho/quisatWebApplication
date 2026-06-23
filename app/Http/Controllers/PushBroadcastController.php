<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushBroadcastJob;
use App\Models\Business;
use App\Models\DeviceToken;
use App\Models\PushBroadcast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PushBroadcastController extends Controller
{
    public function index(): View
    {
        $this->authorizeAccess();

        $query = PushBroadcast::query()->with('creator')->latest();

        if (! $this->isSuperAdmin()) {
            $query->where('business_id', Auth::user()->business_id);
        }

        $broadcasts = $query->paginate(20);

        return view('push-notifications.index', compact('broadcasts'));
    }

    public function create(): View
    {
        $this->authorizeAccess();

        $businesses = $this->isSuperAdmin()
            ? Business::query()->where('id', '!=', 1)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('push-notifications.create', compact('businesses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess();

        $validated = $this->validateBroadcast($request);

        $broadcast = PushBroadcast::create([
            ...$validated,
            'status' => PushBroadcast::STATUS_QUEUED,
            'created_by' => Auth::id(),
        ]);

        SendPushBroadcastJob::dispatch($broadcast);

        return redirect()
            ->route('push-notifications.show', $broadcast)
            ->with('success', 'Notification queued for delivery.');
    }

    public function show(PushBroadcast $pushNotification): View
    {
        $this->authorizeAccess();
        $this->authorizeBroadcast($pushNotification);

        $pushNotification->load('creator', 'business');

        $deviceStats = [
            'total_devices' => $this->deviceCountQuery()->count(),
            'ios' => $this->deviceCountQuery()->where('platform', 'ios')->count(),
            'android' => $this->deviceCountQuery()->where('platform', 'android')->count(),
            'web' => $this->deviceCountQuery()->where('platform', 'web')->count(),
        ];

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

        return [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'audience' => $validated['audience'],
            'business_id' => $businessId,
            'channels' => array_values(array_unique($validated['channels'])),
            'data' => $data,
        ];
    }

    protected function deviceCountQuery()
    {
        $query = DeviceToken::query()->where('is_active', true);

        if (! $this->isSuperAdmin()) {
            $businessId = Auth::user()->business_id;

            $query->where(function ($q) use ($businessId) {
                $q->whereHasMorph('tokenable', [\App\Models\User::class], fn ($uq) => $uq->where('business_id', $businessId))
                    ->orWhereHasMorph('tokenable', [\App\Models\ParentGuardian::class], fn ($pq) => $pq->where('business_id', $businessId));
            });
        }

        return $query;
    }

    protected function authorizeAccess(): void
    {
        if (! Auth::check()) {
            abort(403);
        }
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

    protected function isSuperAdmin(): bool
    {
        return Auth::check() && (int) Auth::user()->business_id === 1;
    }
}
