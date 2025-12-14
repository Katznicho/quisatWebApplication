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

    public function store(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        // Handle both JSON and FormData requests
        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'nullable|string|in:general,urgent,event,reminder',
            'status' => 'nullable|string|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date',
        ];

        // If request has files, it's FormData - validate files
        if ($request->hasFile('attachments')) {
            $rules['channels'] = 'nullable|array';
            $rules['channels.*'] = 'string|in:email,sms,push,in_app';
            $rules['target_roles'] = 'nullable|array';
            $rules['target_roles.*'] = 'string|in:all_users,staff,students,parents';
            $rules['attachments'] = 'nullable|array';
            $rules['attachments.*'] = 'file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,txt,mp4,avi,mov|max:10240';
        } else {
            // JSON request
            $rules['channels'] = 'nullable|array';
            $rules['channels.*'] = 'string|in:email,sms,push,in_app';
            $rules['target_roles'] = 'nullable|array';
            $rules['target_roles.*'] = 'string|in:all_users,staff,students,parents';
            $rules['target_users'] = 'nullable|array';
            $rules['target_users.*'] = 'integer|exists:users,id';
        }

        $validated = $request->validate($rules);

        $attachments = [];
        
        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $mimeType = $file->getMimeType();
                $fileType = str_starts_with($mimeType, 'image/') ? 'image' : 
                           (str_starts_with($mimeType, 'video/') ? 'video' : 'file');
                
                $directory = $fileType === 'image' ? 'announcements/images' : 
                            ($fileType === 'video' ? 'announcements/videos' : 'announcements/files');
                
                $path = $file->store($directory, 'public');
                
                $attachments[] = [
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $fileType,
                    'mime_type' => $mimeType,
                ];
            }
        }

        $announcement = BroadcastAnnouncement::create([
            'business_id' => $businessId,
            'sender_id' => $user->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'] ?? 'general',
            'channels' => $validated['channels'] ?? ['in_app'],
            'target_roles' => $validated['target_roles'] ?? ['all_users'],
            'target_users' => $validated['target_users'] ?? null,
            'status' => $validated['status'] ?? 'published',
            'scheduled_at' => isset($validated['scheduled_at']) ? $validated['scheduled_at'] : null,
            'sent_at' => ($validated['status'] ?? 'published') === 'published' ? now() : null,
            'attachments' => !empty($attachments) ? $attachments : null,
        ]);

        $announcement->load('sender:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Announcement created successfully.',
            'data' => [
                'announcement' => $this->transformAnnouncement($announcement, true),
            ],
        ], 201);
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
            'attachments' => $announcement->attachments ?? [],
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
