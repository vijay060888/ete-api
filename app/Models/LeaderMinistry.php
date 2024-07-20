<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderMinistry extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable= [
       'leaderId',
       'ministryId',
       'status',
       'type',
       'createdBy',
       'updatedBy'
    ];


    public function ministry()
    {
        return $this->belongsTo(Ministry::class, 'ministryId', 'id');

    }
}
