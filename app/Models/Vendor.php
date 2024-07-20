<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Vendor extends Model
{
    use HasFactory,HasUuids;
    protected $table = 'vendor';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'leaderId',
        'partyId',
        'category',
        'name',
        'gst',
        'pan',
        'address',
        'services',
        'website',
        'email',
        'phone',
        'hashTags',
        'media',
        'createdBy',
        'createdAt', 
        'updatedAt', 
        'updatedBy', 
    ];
}
