<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manifesto extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'partyId',
        'electionHistoryId',
        'electionTypeId',
        'manifestoFile',
        'createdBy',
        'updatedBy',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class, 'partyId', 'id');
    }
    public function electionHistory()
    {
        return $this->belongsTo(ElectionHistory::class, 'electionHistoryId', 'id');
    }
}
