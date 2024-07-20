<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DonationTransaction extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'orderId',
        'trackingId',
        'bankRefNo',
        'orderStatus',
        'failureMessage',
        'paymentMode',
        'statusCode',
        'statusMessage',
        'currency',
        'amount',
    ];

}
