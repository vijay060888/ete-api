<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory,HasUuids;
    protected $fillable = [
        'id',
        'achievementtitle',
        'achievementdescription',
        'costincured',
        'hashtags',
        'mediaupload',
        'leader_id',
        'party_id',
        'status',
        'archieveby',
        'archieveType',
        'created_at',
        'updated_at',
        'url',
    ];
}
