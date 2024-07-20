<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionType extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'electionName',
        'stateId',
        'electionTypeDescriptionBrief',
        'electionNumberOfSeats',
        'electionTerm',
        'electionStatus',
        'electionHashtags',
        'createdBy',
        'updatedBy',
    ];

    public function electionHistories()
    {
        return $this->hasMany(ElectionHistory::class, 'electionTypeId', 'id');
    }
    public function state()
    {
        return $this->hasOne(State::class, 'id', 'stateId');
    }
}
