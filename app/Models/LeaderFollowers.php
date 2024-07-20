<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderFollowers extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable= [
       'followerId',
       'leaderId',
       'createdBy',
       'updatedBy'
    ];

public function leader()
{
    return $this->belongsTo(User::class, 'leaderId', 'id');
}

public function leaderDetails()
{
    return $this->belongsTo(User::class, 'leaderId', 'userId');
}

public function follower()
{
    return $this->belongsTo(User::class, 'followerId', 'id');
}

}
