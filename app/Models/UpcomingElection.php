<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpcomingElection extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable=[
        'id',
        'electionTypeId',
        'electionDate',
        'stateId',
        'createdBy',
        'updatedBy'
    ];

    public function state()
    {
        return $this->hasOne(State::class, 'id', 'stateId');
    }

}
