<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Likes extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable= [
       'postByType',
       'postid',
       'LikeByType',
       'LikeById',
       'likeType',
       'createdBy',
       'updatedBy'
    ];

    public function hasLiked($likeByType,$likeById, $postID,$postByType)
    {
        return $this->where('LikeByType', $likeByType)
        ->where('LikeById', $likeById)
        ->where('postid', $postID)
        ->where('postByType',$postByType)
        ->value('likeType');

    }
   
    public function postByLeader()
    {
        return $this->belongsTo(PostByLeader::class, 'postid', 'id');
    }
    public function postByParty()
    {
        return $this->belongsTo(PostByParty::class, 'postid', 'id');
    }
}
