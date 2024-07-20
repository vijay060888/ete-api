<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory,HasUuids;
    
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable=[
        'order_id',
        'tracking_id',
        'order_status',
        'failure_message',
        'payment_mode',
        'card_name',
        'status_code',
        'status_message',
        'currency',
        'amount',
        'billing_name',
        'billing_tel',
        'billing_email',
    ];
}
