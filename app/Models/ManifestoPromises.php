<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManifestoPromises extends Model
{
    use HasFactory,HasUuids;
    
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'manifestoId',
        'manifestoPromisesDepartment',
        'manifestoPromisesPromise',
        'manifestoCountPositive',
        'manifestoCountNegative',
        'manifestoCountShare',
        'likesCount',
        'commentsCount',
        'manifestoShortDescriptions',
        'manifestoPromisesDescriptions',
        'manifestoPromisesIdStatus',
        'createdBy',
        'updatedBy',
    ];
    public function manifesto()
    {
        return $this->belongsTo(Manifesto::class, 'manifestoId', 'id');
    }
}
