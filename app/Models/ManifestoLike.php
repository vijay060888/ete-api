<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManifestoLike extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'likeByType',
        'likeById',
        'likeType',
        'manifestoId',
        'createdBy',
        'updatedBy',
    ];

    public function hasLiked($likeByType,$likeById,$manifestoId)
    {
        return $this->where('likeByType', $likeByType)
        ->where('likeById', $likeById)
        ->where('manifestoId', $manifestoId)
        ->value('likeType');
    }
}
