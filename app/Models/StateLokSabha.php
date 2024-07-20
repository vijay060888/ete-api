<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateLokSabha extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable=[
        'id',
        'stateId',
        'loksabhaId'
    ];
    public function state()
    {
        return $this->belongsTo(State::class, 'stateId', 'id');
    }
    
}
