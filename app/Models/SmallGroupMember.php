<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmallGroupMember extends Model
{
    protected $fillable = [
        'small_group_id',
        'business_id',
        'student_id',
        'enrolled_by_parent_id',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(SmallGroup::class, 'small_group_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'enrolled_by_parent_id');
    }
}
