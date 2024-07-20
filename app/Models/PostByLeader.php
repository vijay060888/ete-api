<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostByLeader extends Model
{
    use HasFactory, HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'authorType',
        'leaderId',
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

    public function postByLeaderMetas()
    {
        return $this->hasMany(PostByLeaderMeta::class, 'postByLeaderId');
    }

    public function pollsByLeaderDetails()
    {
        return $this->hasMany(PollsByLeaderDetails::class, 'postByLeaderId');
    }

    public function pollsByLeaderVote()
    {
        return $this->hasMany(PollsByLeaderVote::class, 'postByLeaderId');
    }
    public function eventsByLeader()
    {
        return $this->hasMany(EventsByLeader::class, 'postByLeaderId');
    }
    public function leaderDetails()
    {
        return $this->belongsTo(UserDetails::class, 'leaderId');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'leaderId');
    }
    public function leader()
    {
        return $this->belongsTo(Leader::class, 'leaderId', 'leadersId');
    }

    public function likes()
    {
        return $this->hasOne(Likes::class, 'postid', 'id');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class, 'postid', 'id');
    }

    public function ad()
    {
        return $this->hasOne(AdPost::class, 'postId', 'id');

    }
}