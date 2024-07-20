<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryByLeader extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable=[
        'id',
        'leaderId',
        'storyContent',
        'storytext',
        'createdBy',
        'updatedBy',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'leaderId')->with('userDetails');
    }
}
