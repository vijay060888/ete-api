<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdPost extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'postId',
        'adsId',
        'authorType',
        'tobeReachedBy',
        'createdAt',
        'updatedAt',
    ];

    public function AdsDetails()
    {
        return $this->belongsTo(Ad::class, 'adsId', 'id');
    }
}
