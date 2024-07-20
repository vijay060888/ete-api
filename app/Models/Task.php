<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable=[
        'id',
        'assignTo',
        'assignBy',
        'assignByType',
        'taskType',
        'taskTitle',
        'taskDescription',
        'subTask',
        'status',
        'startDate',
        'endDate',
        'startTime',
        'endTime',
        'createdBy',
        'updatedBy',
        'userType',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'assignTo', 'id');
    } 
}
