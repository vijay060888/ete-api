<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderElectionHistory extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'leaderId',
        'partyId',
        'electionHistoryId',
        'assemblyId',
        'loksabhaId',
        'electionHistoryLeaderResult',
        'isIndependent',
        'leadInVoting',
        'createdBy',
        'updatedBy',
    ];
}
