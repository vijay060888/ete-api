<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFollowerTag extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable= [
       'userId',
       'followedTags',
       'userType',
       'assembly_id',
       'loksabha_id',
       'createdBy',
       'updatedBy',
    ];
    

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
