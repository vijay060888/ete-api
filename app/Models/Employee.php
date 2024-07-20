<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'employeeToId',
        'employeeCreatedBy',
        'employeeCreatedType',
        'employeeDepartmentId',
        'jobRole',
        'annualCTC',
        'dateOfJoining',
        'referenceName',
        'workExperince',
        'referencePhone',
        'maxEducation',
        'reportingManager',
        'profesionalExperience',
        'comments',
        'status',
        'createdBy',
        'updatedBy',
        'createdAt',
        'updatedAt',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'employeeToId', 'id');
    } 
}
