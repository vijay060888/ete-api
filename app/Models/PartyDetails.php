<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartyDetails extends Model
{
    use HasFactory, HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'partyId',
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
        'createdBy',
        'updatedBy'
    ];
    public function PartyDetails()
    {
        return $this->belongsTo(PartyDetails::class, 'partyId', 'userId');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'leadersId', 'id');
    }
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->createdBy = Auth::id();
            $model->updatedBy = Auth::id();
        });

        self::updating(function ($model) {
            $model->updatedBy = Auth::id();
        });
    }
}
