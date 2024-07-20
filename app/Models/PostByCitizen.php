<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostByCitizen extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'authorType',
        'citizenId',
        'postType',
        'postTitle',
        'likesCount',
        'commentsCount',
        'shareCount',
        'anonymous',
        'hashTags',
        'mention',
        'createdBy',
        'updatedBy',
        'isPublished',
        'abusivetext',
        'isAds',
        'sentiment',
        'political',
        'abusiveimage'
    ];
    public function postByCitizenMetas()
    {
        return $this->hasMany(PostByCitizenMeta::class, 'postByCitizenId');
    }

    public function pollsByCitizenDetails()
    {
        return $this->hasMany(PollsByCitizenDetails::class, 'postByCitizenId');
    }
    public function  pollsByCitizenVote()
    {
        return $this->hasMany(PollsByCitizenVote::class, 'postByCitizenId');

    }
    // public function eventsByLeader()
    // {
    //     return $this->hasMany(EventsByLeader::class, 'postByLeaderId');
    // }
    public function citizenDetails()
    {
        return $this->belongsTo(UserDetails::class, 'citizenId');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'citizenId');
    }
    public function likes()
    {
        return $this->hasOne(Likes::class, 'postid', 'id');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class, 'postid', 'id');
    }
}
