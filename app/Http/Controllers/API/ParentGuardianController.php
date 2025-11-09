<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use Illuminate\Http\Request;

class ParentGuardianController extends Controller
{
    /**
     * List parents/guardians for the authenticated business.
     */
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');

        $query = ParentGuardian::query()
            ->with([
                'students:id,parent_guardian_id,first_name,last_name,student_id,class_room_id,status',
                'students.classRoom:id,name,code',
            ])
            ->where('business_id', $businessId)
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 100) : 50;

        $parents = $query->paginate($perPage);

        $parents->getCollection()->transform(function (ParentGuardian $parent) {
            return $this->transformParent($parent);
        });

        return response()->json([
            'success' => true,
            'message' => 'Parents and guardians retrieved successfully.',
            'data' => [
                'parents' => $parents->items(),
                'pagination' => [
                    'current_page' => $parents->currentPage(),
                    'per_page' => $parents->perPage(),
                    'total' => $parents->total(),
                    'last_page' => $parents->lastPage(),
                    'has_more' => $parents->hasMorePages(),
                ],
            ],
        ]);
    }

    /**
     * Show a single parent/guardian and their students.
     */
    public function show(Request $request, $parent)
    {
        $businessId = $request->get('business_id');

        $parentGuardian = ParentGuardian::query()
            ->with([
                'students:id,parent_guardian_id,first_name,last_name,student_id,class_room_id,status,gender,date_of_birth',
                'students.classRoom:id,name,code',
            ])
            ->where('business_id', $businessId)
            ->where(function ($q) use ($parent) {
                $q->where('uuid', $parent);
                if (is_numeric($parent)) {
                    $q->orWhere('id', $parent);
                }
            })
            ->first();

        if (!$parentGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Parent or guardian not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Parent or guardian retrieved successfully.',
            'data' => [
                'parent' => $this->transformParent($parentGuardian, true),
            ],
        ]);
    }

    /**
     * Transform a parent/guardian record for API response.
     */
    protected function transformParent(ParentGuardian $parent, bool $includeDetails = false): array
    {
        $students = $parent->students->map(function ($student) {
            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'student_id' => $student->student_id,
                'status' => $student->status,
                'gender' => $student->gender,
                'class_room' => $student->classRoom ? [
                    'id' => $student->classRoom->id,
                    'name' => $student->classRoom->name,
                    'code' => $student->classRoom->code,
                ] : null,
                'avatar_url' => $this->generateAvatarUrl($student->full_name),
            ];
        })->values()->all();

        $data = [
            'id' => $parent->id,
            'uuid' => $parent->uuid,
            'first_name' => $parent->first_name,
            'last_name' => $parent->last_name,
            'full_name' => $parent->full_name,
            'email' => $parent->email,
            'phone' => $parent->phone,
            'relationship' => $parent->relationship,
            'status' => $parent->status,
            'students' => $students,
            'avatar_url' => $this->generateAvatarUrl($parent->full_name),
        ];

        if ($includeDetails) {
            $data['address'] = $parent->address;
            $data['city'] = $parent->city;
            $data['country'] = $parent->country;
            $data['occupation'] = $parent->occupation;
            $data['emergency_contact'] = $parent->emergency_contact;
            $data['created_at'] = optional($parent->created_at)->toIso8601String();
        }

        return $data;
    }

    /**
     * Generate a consistent avatar URL for users without profile photos.
     */
    protected function generateAvatarUrl(?string $name): string
    {
        $encoded = urlencode($name ?: 'Parent');

        return "https://ui-avatars.com/api/?name={$encoded}&background=6366F1&color=ffffff&size=128";
    }
}

