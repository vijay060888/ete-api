<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderDetails extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'leadersId',
        'followersCount',
        'youngUsers',
        'middledAgeUsers',
        'maleFollowers',
        'femaleFollowers',
        'transgenderFollowers',
        'appreciatePostCount',
        'likePostCount',
        'carePostCount',
        'unlikesPostCount',
        'sadPostCount',
        'issuedResolvedCount',
        'postFrequency',
        'sentiments',
        'responseTime',
        'createdAt',
        'updatedAt',
    ];
    public function leaderDetails()
    {
        return $this->belongsTo(UserDetails::class, 'leadersId', 'userId');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'leadersId', 'id');
    }
}
