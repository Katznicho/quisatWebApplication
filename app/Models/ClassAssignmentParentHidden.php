<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassAssignmentParentHidden extends Model
{
    use HasFactory;

    protected $table = 'class_assignment_parent_hidden';

    protected $fillable = [
        'assignment_id',
        'parent_guardian_id',
    ];

    public function assignment()
    {
        return $this->belongsTo(ClassAssignment::class, 'assignment_id');
    }

    public function parentGuardian()
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_guardian_id');
    }
}
