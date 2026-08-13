<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationsQuery($request)
            ->latest()
            ->paginate(min((int) $request->get('per_page', 20), 50));

        return response()->json([
            'success' => true,
            'data' => $notifications->through(fn (UserNotification $n) => $this->format($n)),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $this->notificationsQuery($request)
                    ->whereNull('read_at')
                    ->count(),
            ],
        ]);
    }

    public function markRead(Request $request, string $uuid): JsonResponse
    {
        $notification = $this->notificationsQuery($request)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $notification->markAsRead();

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

        $notification->delete();

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
}
