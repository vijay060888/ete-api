<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'userId',
        'voterImage',
        'profileImage',
        'loksabhaId',
        'assemblyId',
        'boothId',
        'isVoterIdVerified',
        'createdBy',
        'updatedBy',
        'createdAt',
        'updatedAt',
    ];
    public function getLokSabhaName(){
        if($this->loksabhaId!=null){
            $data=LokSabhaConsituency::where('id',$this->loksabhaId)->select('name')->first();
            return $data;
        }else{
            return '';
        }
    }

    public function getAssemblyName(){
        if($this->loksabhaId!=null){
            $data=AssemblyConsituency::where('id',$this->assemblyId)->select('name')->first();
            return $data;
        }else{
            return '';
        }
    }

    public function getboothName(){
        if($this->boothId!=null){
            $data=Booth::where('id',$this->boothId)->select('name')->first();
            return $data;
        }else{
            return '';
        }
    }
}


