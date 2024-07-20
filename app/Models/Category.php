<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Category extends Model
{
    use HasFactory,HasUuids;
    protected $table = 'categories';
    protected $fillable = [
        'id',
        'categoryName',
        'status',
        'createdBy',
        'createdAt', 
        'updatedAt', 
        'updatedBy', 
    ];


}
