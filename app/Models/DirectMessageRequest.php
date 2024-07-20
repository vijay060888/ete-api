<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectMessageRequest extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'id',
        'senderId',
        'senderType',
        'receiverId',
        'receiverType',
        'status',
        'message',
        'created_at',
        'updated_at',
        'media',
    ];
}
