<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollsByCitizenDetails extends Model
{
    use HasFactory, HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'postByCitizenId',
        'pollOption',
        'optionCount',
        'createdBy',
        'updatedBy',
    ];
    public function pollsByCitizenVotes()
    {
        return $this->hasMany(PollsByCitizenVote::class, 'postByCitizenId');
    }

    public function userVote($userId, $postId)
    {
        $vote = PollsByCitizenVote::where('userId', $userId)->where('postByCitizenId', $postId)->first();
        if ($vote) {
            $selectedOption = $vote->selectedOption;
            return $selectedOption;
        }

        return null;
    }
}
