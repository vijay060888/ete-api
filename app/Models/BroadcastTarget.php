<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastTarget extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'broadcastId',
        'stateId',
        'constituency',
        'constituencyType',
        'gender',
        'minAge',
        'maxAge',
        'createdAt',
        'updatedAt',
    ];
}
