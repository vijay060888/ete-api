<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostByParty extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'authorType',
        'partyId',
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

    public function party()
    {
        return $this->belongsTo(Party::class, 'partyId');
    }
    public function postByPartyMetas()
    {
        return $this->hasMany(PostByPartyMeta::class, 'postByPartyId');
    }

    public function pollsByPartyDetails()
    {
        return $this->hasMany(PollsByPartyDetails::class, 'postByPartyId');
    }

    public function pollsByPartyVote()
    {
        return $this->hasMany(PollsByPartyVote::class, 'postByPartyId');
    }
    public function eventsByParty()
    {
        return $this->hasMany(EventsByParty::class, 'postByPartyId');
    }
    public function leaderDetails()
    {
        return $this->belongsTo(Party::class, 'partyId');
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
