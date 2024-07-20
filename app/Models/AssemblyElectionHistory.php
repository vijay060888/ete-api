<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssemblyElectionHistory extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'electionHistoryId',
        'electionTypeId',
        'percentageinParliament',
        'majority',
        'turnout',
        'singleLargestPartyId',
        'government',
        'votesPercentage',
        'createdBy',
        'updatedBy',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class, 'singleLargestPartyId', 'id');
    }
    public function electionHistory()
    {
        return $this->belongsTo(ElectionHistory::class, 'electionHistoryId', 'id');
    }

    public function electionTypeDetails()
    {
        return $this->belongsTo(ElectionType::class, 'electionTypeId', 'id');
    }
}
