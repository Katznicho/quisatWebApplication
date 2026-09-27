<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ModelActivityObserver
{
    public function created(Model $model)
    {
        $this->log('created', $model);
    }

    public function updated(Model $model)
    {
        $this->log('updated', $model, $model->getOriginal());
    }

    public function deleted(Model $model)
    {
        $this->log('deleted', $model, $model->getOriginal());
    }

    protected function log(string $action, Model $model, $oldData = null)
    {
        // Skip logging if not authenticated (e.g., during seeding or console commands)
        if (! Auth::check()) {
            return;
        }

        $userId = Auth::id();

        // The users row is already gone when this runs. activity_logs.user_id
        // references users.id, so pointing the new row at the deleted account fails.
        if (
            $action === 'deleted'
            && $model instanceof User
            && (int) $model->getKey() === (int) $userId
        ) {
            $userId = null;
        }

        ActivityLog::create([
            'user_id'     => $userId,
            'business_id' => optional(Auth::user())->business_id,
            'branch_id'   => optional(Auth::user())->branch_id,
            'model_type'  => get_class($model),
            'model_id'    => $model->getKey(),
            'action'      => $action,
            'old_values'  => $this->snapshot($model, $oldData),
            'new_values'  => in_array($action, ['created', 'updated'], true)
                ? $this->snapshot($model, $model->getAttributes())
                : null,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->header('User-Agent'),
            'description' => '',
        ]);
    }

    /**
     * Model snapshot without secrets such as password hashes.
     * ActivityLog casts these columns to array, so return an array.
     *
     * @return array<string, mixed>|null
     */
    protected function snapshot(Model $model, $data): ?array
    {
        if ($data === null) {
            return null;
        }

        if (! is_array($data)) {
            $data = (array) $data;
        }

        foreach ($model->getHidden() as $hidden) {
            unset($data[$hidden]);
        }

        return $data;
    }
}
