<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NewsAndMedia extends Model
{
    use HasFactory,HasUuids;
    protected $fillable = [
        'id',
        'newsttitle',
        'newsdescription',
        'costincured',
        'hashtags',
        'mediaupload',
        'leader_id',
        'party_id',
        'status',
        'url',
        'archieveby',
        'archieveType',
        'created_at',
        'updated_at',
    ];
}
