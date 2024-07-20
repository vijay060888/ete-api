<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryByParty extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable=[
        'id',
        'partyId',
        'storyContent',
        'storytext',
        'createdBy',
        'updatedBy',
    ];
    public function parties()
    {
        return $this->belongsTo(Party::class, 'partyId');
    }
}
