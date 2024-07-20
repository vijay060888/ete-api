<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManifestoPromisesComments extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'manifestoPromisesId',
        'commentsDepartment',
        'commentAttachment',
        'userType',
        'userId',
        'departmentURL',
        'manifestoPromisesCommentHeader',
        'manifestoPromisesCommentText',
        'createdBy',
        'updatedBy',
    ];

    public function manifestoPromises()
    {
        return $this->belongsTo(ManifestoPromises::class, 'manifestoPromisesId', 'id');
    }
}
