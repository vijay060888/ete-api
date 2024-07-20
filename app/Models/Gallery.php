<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Gallery extends Model
{
    use HasFactory,HasUuids;
    protected $fillable = [
        'id',
        'leader_id',
        'party_id',
        'party_admin_id',
        'archive_by',
        'archive_by_type',
        'title',
        'description',
        'hashtag', 
        'status', 
        'media', 
        'created_at', 
        'updated_at', 
    ];
}
