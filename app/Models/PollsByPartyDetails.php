<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollsByPartyDetails extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'postByPartyId',
        'pollOption',
        'optionCount',
        'createdBy',
        'updatedBy',
    ];

    public function pollsByPartyVotes()
    {
        return $this->hasMany(PollsByPartyVote::class, 'postByPartyId');
    }

    public function userVote($userId, $postId)
    {
        $vote = PollsByPartyVote::where('userId', $userId)->where('postByPartyId', $postId)->first();
        if ($vote) {
            $selectedOption = $vote->selectedOption;
            return $selectedOption;
        }
        return null;
    }
}
