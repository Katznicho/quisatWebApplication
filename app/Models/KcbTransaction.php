<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KcbTransaction extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'request_id',
        'transaction_reference',
        'customer_reference',
        'organization_reference',
        'organization_short_code',
        'channel_code',
        'till_number',
        'transaction_amount',
        'bill_amount',
        'currency',
        'customer_name',
        'customer_mobile_number',
        'balance',
        'narration',
        'credit_account_identifier',
        'bill_type',
        'status_code',
        'status_message',
        'transaction_timestamp',
        'transaction_type',
        'raw_request',
        'raw_response',
    ];

    protected $casts = [
        'transaction_amount' => 'decimal:2',
        'bill_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'transaction_timestamp' => 'datetime',
    ];
}
