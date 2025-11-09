<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BroadcastAnnouncement;
use App\Models\User;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = BroadcastAnnouncement::query()
            ->with(['sender:id,name,email'])
            ->where('business_id', $businessId)
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($user instanceof User && $user->branch_id) {
            // Future: filter by branch-specific announcements.
        }

        $perPage = (int) $request->query('per_page', 25);
        $perPage = $perPage > 0 ? min($perPage, 100) : 25;

        $announcements = $query->paginate($perPage);
        $announcements->getCollection()->transform(fn ($announcement) => $this->transformAnnouncement($announcement));

        return response()->json([
            'success' => true,
            'message' => 'Announcements fetched successfully.',
            'data' => [
                'announcements' => $announcements->items(),
                'pagination' => [
                    'current_page' => $announcements->currentPage(),
                    'per_page' => $announcements->perPage(),
                    'total' => $announcements->total(),
                    'last_page' => $announcements->lastPage(),
                    'has_more' => $announcements->hasMorePages(),
                ],
            ],
        ]);
    }

    public function show(Request $request, $announcement)
    {
        $businessId = $request->get('business_id');

        $record = BroadcastAnnouncement::query()
            ->with(['sender:id,name,email'])
            ->where('business_id', $businessId)
            ->where(function ($q) use ($announcement) {
                $q->where('id', $announcement);
            })
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Announcement retrieved successfully.',
            'data' => [
                'announcement' => $this->transformAnnouncement($record, true),
            ],
        ]);
    }

    protected function transformAnnouncement(BroadcastAnnouncement $announcement, bool $includeMeta = false): array
    {
        $data = [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'content' => $announcement->content,
            'type' => $announcement->type,
            'status' => $announcement->status,
            'channels' => $announcement->channels ?? [],
            'target_roles' => $announcement->target_roles ?? [],
            'target_users' => $announcement->target_users ?? [],
            'scheduled_at' => optional($announcement->scheduled_at)->toIso8601String(),
            'sent_at' => optional($announcement->sent_at)->toIso8601String(),
            'created_at' => optional($announcement->created_at)->toIso8601String(),
            'updated_at' => optional($announcement->updated_at)->toIso8601String(),
            'sender' => $announcement->sender ? [
                'id' => $announcement->sender->id,
                'name' => $announcement->sender->name,
                'email' => $announcement->sender->email,
            ] : null,
        ];

        if ($includeMeta) {
            $data['can_edit'] = false; // Placeholder for future permission logic
        }

        return $data;
    }
}
