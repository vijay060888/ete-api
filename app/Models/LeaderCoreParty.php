<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderCoreParty extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable= [
       'leaderId',
       'corePartyId',
       'createdBy',
       'updatedBy'
    ];

    public function party()
    {
        return $this->hasOne(Party::class, 'id', 'corePartyId');
    }
}
