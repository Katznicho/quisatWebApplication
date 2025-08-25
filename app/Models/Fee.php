<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Fee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'student_id',
        'term_id',
        'fee_type',
        'amount',
        'amount_paid',
        'balance',
        'due_date',
        'payment_status',
        'payment_method',
        'payment_date',
        'receipt_number',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'due_date' => 'date',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($fee) {
            $fee->uuid = Str::uuid();
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
