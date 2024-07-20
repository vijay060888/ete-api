<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsituencyPoliticalTimeLine extends Model
{
    use HasFactory,HasUuids;

    protected $fillable=[
        'id',
        'consituencyId',
        'consituencyType',
        'leaderId',
        'inPowerDate',
        'rullingPartyId',
        'createdBy',
        'updatedBy',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'leaderId', 'id');
    }
    public function userDetails()
    {
        return $this->belongsTo(UserDetails::class, 'leaderId', 'userId');
    }

    public function leader()
    {
        return $this->belongsTo(Leader::class, 'leaderId', 'leadersId');
    }
}
