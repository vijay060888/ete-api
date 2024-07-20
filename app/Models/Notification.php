<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class Notification extends Model
{
    use HasFactory,HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'type',
        'typeId',
        'isRead',
        'profileImage',
        'userType',
        'userId',
        'notificationMessage',
        'createdBy',
        'updatedBy',
        'broadcast_id',
        'notificationtype',
        'notificationtypeid',
        'notificationcategory',
    ];
}
 