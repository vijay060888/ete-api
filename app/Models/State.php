<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'code',
        'name',
        'map',
        'officialPage',
        'descriptionShort',
        'descriptionBrief',
        'population',
        'populationMale',
        'populationFemale',
        'populationElectors',
        'populationElectorsMale',
        'populationElectorsFemale',
        'gdp',
        'languages',
        'hashTags',
        'createdBy',
        'updatedBy',
    ];
}

