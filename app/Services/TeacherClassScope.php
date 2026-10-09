<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TeacherClassScope
{
    public function seesAllClasses(User $user): bool
    {
        return $user->isAdmin() || $user->isBusinessAdmin();
    }

    /**
     * @return array<int, int>
     */
    public function assignedClassIds(User $user, int $businessId): array
    {
        $query = $this->classQuery($user, $businessId);

        if (! $this->seesAllClasses($user)) {
            $ids = collect();
            if ($user->class_room_id) {
                $ids->push((int) $user->class_room_id);
            }

            $taught = Timetable::query()
                ->where('business_id', $businessId)
                ->where('teacher_id', $user->id)
                ->whereNotNull('class_room_id')
                ->pluck('class_room_id');

            $ids = $ids->merge($taught)->map(fn ($id) => (int) $id)->unique()->filter()->values();
            if ($ids->isEmpty()) {
                return [];
            }

            $query->whereIn('id', $ids->all());
        }

        return $query->orderBy('name')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ClassRoom>
     */
    public function assignedClasses(User $user, int $businessId)
    {
        $ids = $this->assignedClassIds($user, $businessId);
        if ($ids === []) {
            return collect();
        }

        return ClassRoom::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'branch_id']);
    }

    public function canAccessClass(User $user, int $businessId, int $classRoomId): bool
    {
        return in_array($classRoomId, $this->assignedClassIds($user, $businessId), true);
    }

    protected function classQuery(User $user, int $businessId): Builder
    {
        $query = ClassRoom::query()
            ->where('business_id', $businessId)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'active');
            });

        if ($user->branch_id && ! $this->seesAllClasses($user)) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('branch_id')->orWhere('branch_id', $user->branch_id);
            });
        }

        return $query;
    }
}
