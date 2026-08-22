<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 50);
        $page = max((int) $request->get('page', 1), 1);

        $notifications = $this->deduplicateNotifications(
            $this->notificationsQuery($request)->latest()->limit(500)->get()
        );

        $pageItems = $notifications->forPage($page, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $pageItems,
            $notifications->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'data' => $paginator->through(fn (UserNotification $n) => $this->format($n)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'unread_count' => $notifications->whereNull('read_at')->count(),
            ],
        ]);
    }

    public function markRead(Request $request, string $uuid): JsonResponse
    {
        $notification = $this->notificationsQuery($request)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->siblingNotifications($request, $notification)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => $this->format($notification->fresh()),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notificationsQuery($request)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $notification = $this->notificationsQuery($request)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->siblingNotifications($request, $notification)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted.',
        ]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $deleted = $this->notificationsQuery($request)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications deleted.',
            'data' => ['deleted' => $deleted],
        ]);
    }

    protected function format(UserNotification $notification): array
    {
        $imageUrl = $notification->data['imageUrl'] ?? null;

        return [
            'id' => $notification->uuid,
            'title' => $notification->title,
            'body' => $notification->body,
            'image_url' => $imageUrl,
            'data' => $notification->data,
            'read' => $notification->read_at !== null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    protected function notificationsQuery(Request $request): Builder
    {
        $owners = $this->notifiableOwners($request);

        return UserNotification::query()->where(function (Builder $query) use ($owners) {
            foreach ($owners as [$type, $id]) {
                $query->orWhere(function (Builder $ownerQuery) use ($type, $id) {
                    $ownerQuery
                        ->where('notifiable_type', $type)
                        ->where('notifiable_id', $id);
                });
            }
        });
    }

    /**
     * @return array<int, array{0: class-string, 1: int|string}>
     */
    protected function notifiableOwners(Request $request): array
    {
        $owner = $request->user();
        $owners = [[$owner::class, $owner->getKey()]];
        $email = strtolower(trim((string) ($owner->email ?? '')));

        if ($email === '') {
            return $owners;
        }

        if ($owner instanceof ParentGuardian) {
            $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
            if ($user) {
                $owners[] = [$user::class, $user->getKey()];
            }
        }

        if ($owner instanceof User) {
            $parent = ParentGuardian::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
            if ($parent) {
                $owners[] = [$parent::class, $parent->getKey()];
            }
        }

        return $owners;
    }

    /**
     * @param  Collection<int, UserNotification>  $notifications
     * @return Collection<int, UserNotification>
     */
    protected function deduplicateNotifications(Collection $notifications): Collection
    {
        $seen = [];

        return $notifications
            ->filter(function (UserNotification $notification) use (&$seen) {
                $key = $notification->deduplicationKey();
                if (isset($seen[$key])) {
                    return false;
                }

                $seen[$key] = true;

                return true;
            })
            ->values();
    }

    protected function siblingNotifications(Request $request, UserNotification $notification): Builder
    {
        $query = $this->notificationsQuery($request);

        if ($notification->push_broadcast_id) {
            return $query->where('push_broadcast_id', $notification->push_broadcast_id);
        }

        $data = $notification->data ?? [];
        foreach (['broadcast_id', 'message_id', 'announcement_id', 'assignment_id', 'event_id', 'fee_id'] as $field) {
            if (! empty($data[$field])) {
                return $query->where("data->{$field}", (string) $data[$field]);
            }
        }

        return $query
            ->where('title', $notification->title)
            ->where('body', $notification->body)
            ->whereBetween('created_at', [
                $notification->created_at?->copy()->subSeconds(5),
                $notification->created_at?->copy()->addSeconds(5),
            ]);
    }
}
