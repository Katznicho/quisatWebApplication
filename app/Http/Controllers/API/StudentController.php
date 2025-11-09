<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Return the list of classes available to the authenticated user.
     */
    public function classes(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = ClassRoom::query()
            ->with(['branch:id,name,code'])
            ->withCount(['students' => function ($query) {
                $query->whereNull('students.deleted_at');
            }])
            ->where('business_id', $businessId)
            ->orderBy('name');

        if ($user instanceof User && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        if ($request->boolean('only_active', true)) {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'active');
            });
        }

        $classes = $query->get()->map(function (ClassRoom $classRoom) {
            return [
                'id' => $classRoom->id,
                'uuid' => $classRoom->uuid,
                'name' => $classRoom->name,
                'code' => $classRoom->code,
                'description' => $classRoom->description,
                'capacity' => $classRoom->capacity,
                'status' => $classRoom->status,
                'students_count' => $classRoom->students_count,
                'branch' => $classRoom->branch ? [
                    'id' => $classRoom->branch->id,
                    'name' => $classRoom->branch->name,
                    'code' => $classRoom->branch->code,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Classes retrieved successfully.',
            'data' => [
                'classes' => $classes,
            ],
        ]);
    }

    /**
     * Return students for the authenticated business, optionally filtered.
     */
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = Student::query()
            ->with([
                'classRoom:id,uuid,name,code',
                'branch:id,name,code',
                'parentGuardian:id,uuid,first_name,last_name,email,phone,relationship',
            ])
            ->where('business_id', $businessId);

        if ($user instanceof User && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        if ($classId = $request->query('class_room_id')) {
            $query->where('class_room_id', $classId);
        }

        if ($classUuid = $request->query('class_room_uuid')) {
            $query->whereHas('classRoom', function ($q) use ($classUuid) {
                $q->where('uuid', $classUuid);
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('student_id', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 100) : 50;

        $students = $query->orderBy('first_name')->orderBy('last_name')->paginate($perPage);
        $students->getCollection()->transform(function (Student $student) {
            return $this->transformStudent($student);
        });

        return response()->json([
            'success' => true,
            'message' => 'Students retrieved successfully.',
            'data' => [
                'students' => $students->items(),
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                    'last_page' => $students->lastPage(),
                    'has_more' => $students->hasMorePages(),
                ],
            ],
        ]);
    }

    /**
     * Show a single student record.
     */
    public function show(Request $request, $student)
    {
        $businessId = $request->get('business_id');
        $user = $request->get('authenticated_user');

        $query = Student::query()
            ->with([
                'classRoom:id,uuid,name,code,description',
                'branch:id,name,code',
                'parentGuardian:id,uuid,first_name,last_name,email,phone,relationship,address,city,country',
            ])
            ->where('business_id', $businessId);

        if ($user instanceof User && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        $studentRecord = $query->where(function ($q) use ($student) {
            $q->where('uuid', $student);

            if (is_numeric($student)) {
                $q->orWhere('id', $student);
            }
        })->first();

        if (!$studentRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Student retrieved successfully.',
            'data' => [
                'student' => $this->transformStudent($studentRecord, true),
            ],
        ]);
    }

    /**
     * Transform the student model to the API response structure.
     */
    protected function transformStudent(Student $student, bool $includeDetails = false): array
    {
        $student->loadMissing([
            'classRoom:id,uuid,name,code,description',
            'branch:id,name,code',
            'parentGuardian:id,uuid,first_name,last_name,email,phone,relationship,address,city,country',
        ]);

        $parent = $student->parentGuardian;
        $classRoom = $student->classRoom;
        $branch = $student->branch;

        $data = [
            'id' => $student->id,
            'uuid' => $student->uuid,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'full_name' => $student->full_name,
            'email' => $student->email,
            'phone' => $student->phone,
            'gender' => $student->gender,
            'date_of_birth' => optional($student->date_of_birth)->toDateString(),
            'age' => optional($student->date_of_birth)->age,
            'student_id' => $student->student_id,
            'admission_date' => optional($student->admission_date)->toDateString(),
            'status' => $student->status,
            'avatar_url' => $this->generateAvatarUrl($student),
            'class_room' => $classRoom ? [
                'id' => $classRoom->id,
                'uuid' => $classRoom->uuid,
                'name' => $classRoom->name,
                'code' => $classRoom->code,
                'description' => $classRoom->description,
            ] : null,
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
            ] : null,
            'parents' => $parent ? [[
                'id' => $parent->id,
                'uuid' => $parent->uuid,
                'name' => $parent->full_name,
                'relationship' => $parent->relationship,
                'email' => $parent->email,
                'contact' => $parent->phone,
                'address' => $parent->address,
                'city' => $parent->city,
                'country' => $parent->country,
            ]] : [],
        ];

        if ($includeDetails) {
            $data['address'] = $student->address;
            $data['city'] = $student->city;
            $data['country'] = $student->country;
        }

        return $data;
    }

    /**
     * Generate a placeholder avatar URL for the student.
     */
    protected function generateAvatarUrl(Student $student): string
    {
        $name = trim($student->first_name . ' ' . $student->last_name);
        $encodedName = urlencode($name ?: 'Student');

        return "https://ui-avatars.com/api/?name={$encodedName}&background=4A90E2&color=ffffff&size=128";
    }
}

