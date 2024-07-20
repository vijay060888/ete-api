<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageRequest extends Model
{
    use HasFactory, HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';


    protected $fillable = [
        'id',
        'pageType',
        'stateId',
        'partyId',
        'assemblyId',
        'requestType',
        'loksabhaId',
        'status',
        'partyName',
        'requestedBy',
        'createdBy',
        'updatedBy'
    ];
    public function party()
    {
        return $this->belongsTo(Party::class, 'partyId', 'id');
    }
}