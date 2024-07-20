<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartyFollowers extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'followerId',
        'partyId',
        'createdBy',
        'updatedBy',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class, 'partyId', 'id');
    }
    public function follower()
    {
        return $this->belongsTo(User::class, 'followerId', 'id');
    }
}
