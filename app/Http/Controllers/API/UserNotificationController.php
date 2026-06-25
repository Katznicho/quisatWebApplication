<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $owner = $request->user();

        $notifications = UserNotification::query()
            ->where('notifiable_type', $owner::class)
            ->where('notifiable_id', $owner->getKey())
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
                'unread_count' => UserNotification::query()
                    ->where('notifiable_type', $owner::class)
                    ->where('notifiable_id', $owner->getKey())
                    ->whereNull('read_at')
                    ->count(),
            ],
        ]);
    }

    public function markRead(Request $request, string $uuid): JsonResponse
    {
        $owner = $request->user();

        $notification = UserNotification::query()
            ->where('uuid', $uuid)
            ->where('notifiable_type', $owner::class)
            ->where('notifiable_id', $owner->getKey())
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
        $owner = $request->user();

        UserNotification::query()
            ->where('notifiable_type', $owner::class)
            ->where('notifiable_id', $owner->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
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
}
