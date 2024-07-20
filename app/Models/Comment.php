<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable=[
        'id',
        'postByType',
        'postid',
        'commentByType',
        'commentById',
        'content',
        'repliesCount',
        'commentId',
        'createdBy',
        'updatedBy',
    ];

    public function postByParty()
    {
        return $this->belongsTo(PostByParty::class, 'postid', 'id');
    }
    public function postByLeader()
    {
        return $this->belongsTo(PostByLeader::class, 'postid', 'id');
    }
}
