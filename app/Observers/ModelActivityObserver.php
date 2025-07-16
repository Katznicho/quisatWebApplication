<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Models\ActivityLog;
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
        if (!Auth::check()) {
            return;
        }

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'business_id' => optional(Auth::user())->business_id,
            'branch_id'   => optional(Auth::user())->branch_id,
            'model_type'  => get_class($model),
            'model_id'    => $model->getKey(),
            'action'      => $action,
            'old_values'  => $oldData ? json_encode($oldData) : null,
            'new_values'  => in_array($action, ['created', 'updated']) ? json_encode($model->getAttributes()) : null,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->header('User-Agent'),
            'description' => '',
        ]);
    }
}
