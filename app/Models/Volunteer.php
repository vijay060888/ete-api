<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Volunteer extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'volunteersCode',
        'volunteersTo',
        'volunteersCreatedType',
        'volunteersCreatedTypeId',
        'volunterDepartmentType',
        'dateOfJoining',
        'comments',
        'status',
        'reportingManager',
        'maxEducation',
        'profesionalExperience',
        'createdBy',
        'updatedBy',
        'createdAt',
        'updatedAt',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'volunteersTo', 'id');
    } 
}
