<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostByCitizenMeta extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'postByCitizenId',
        'postDescriptions',
        'imageUrl1',
        'imageUrl2',
        'imageUrl3',
        'imageUrl4',
        'ideaDepartment',
        'PollendDate',
        'pollendTime',
        'complaintLocation',
        'createdBy',
        'updatedBy',
    ];
}
