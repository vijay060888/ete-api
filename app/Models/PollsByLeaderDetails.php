<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollsByLeaderDetails extends Model
{
    use HasFactory, HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'postByLeaderId',
        'pollOption',
        'optionCount',
        'createdBy',
        'updatedBy',
    ];
    public function pollsByLeaderVotes()
    {
        return $this->hasMany(PollsByLeaderVote::class, 'postByLeaderId');
    }

    public function userVote($userId, $postId)
    {
        $vote = PollsByLeaderVote::where('userId', $userId)->where('postByLeaderId', $postId)->first();
        if ($vote) {
            $selectedOption = $vote->selectedOption;
            return $selectedOption;
        }

        return null;
    }
}