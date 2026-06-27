<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class ContentViewService
{
    public function record(?Model $model): void
    {
        if ($model) {
            $model->increment('views_count');
        }
    }
}
