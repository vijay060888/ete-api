<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssemblyConsituency extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'code',
        'name',
        'type',
        'map',
        'officialPage',
        'logo',
        'descriptionShort',
        'descriptionBrief',
        'population',
        'populationMale',
        'populationFemale',
        'populationElectors',
        'populationElectorsMale',
        'populationElectorsFemale',
        'languages',
        'hashTags',
        'districId',
        'createdBy',
        'updatedBy',
        'loksabhaId'
    ];
    public function stateDetails()
    {
      return $this->belongsTo(StateAssembly::class, 'id', 'assemblyId');

    }

}
