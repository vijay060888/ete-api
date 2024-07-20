<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLogActivity extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable=[
        'id',
        'userId',
        'location',
        'deviceType',
        'ipAddress',
        'deviceId',
        'IpAddressOwner',
        'status',
        'createdBy',
        'updatedBy'
    ];

}