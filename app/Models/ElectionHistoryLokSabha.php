<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionHistoryLokSabha extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'electionTypeId',
        'rulingParty',
        'oppositionParty',
        'isAssociation',
        'loksabhaId',
        'electionDate',
        'createdBy',
        'updatedBy',
    ];
}
