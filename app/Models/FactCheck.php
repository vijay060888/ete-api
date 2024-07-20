<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactCheck extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'userId',
        'postId',
        'userType',
        'subject', 
        'description',
        'hashTag',
        'url',
        'media',
        'status',
        'likesCount',
        'commentsCount',
        'shareCount',
        'isCreatedByAdmin',
        'createdBy',
        'updatedBy',
        'createdAt',
        'updatedAt',
    ];
}
