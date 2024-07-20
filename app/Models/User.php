<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Auth;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    public $guard_name = 'api';

    protected $fillable = [
        'userName',
        'firstName',
        'lastName',
        'aadharNumber',
        'aadharNumberView',
        'voterId',
        'password',
        'gender',
        'DOB',
        'tokenVerify',
        'phoneNumber',
        'email',
        'address',
        'state',
        'district',
        'cityTown',
        'pinCode',
        'educationPG',
        'educationUG',
        'profesionalExperience',
        'profesionalDepartment',
        'salary',
        'status',
        'forgotPassword',
        'lastLogin',
        'loginCount',
        'privacy',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return ['userType' => Auth::user()->getRoleNames()[0]];
    }
    public function userDetails()
    {
        return $this->hasOne(UserDetails::class, 'userId');
    }
    public function userAddress()
    {
        return $this->hasOne(UserAddress::class, 'userId');
    }
    public function getProfilePicture()
    {
        return $this->id;
    }
    public function getFullName()
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function leaderDetails()
    {
        return $this->hasOne(Leader::class, 'leadersId');
    }
    public function getDeviceKey()
    {
        
    }
}