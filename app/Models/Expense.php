<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'id',
        'totalExpense',
        'expenseBy', 
        'expenseTowards', 
        'paymentMode', 
        'nameOfVendor', 
        'Description', 
        'expenseDate', 
        'transaction', 
        'hashTag', 
        'invoice', 
        'transaction',
        'expenseCreatedBy', 
        'expenseCreatedByType',
        'remarks',
        'authorize',
        'createdBy',
        'updatedBy',
        'createdAt',
        'updatedAt',
    ];

    // public function user(): BelongsTo
    // {
    //     //return $this->belongsTo(User::class);
    //     return $this->belongsTo(User::class, 'expenseCreatedBy', 'id');
    // }

    public function user()
    {
        return $this->belongsTo(User::class, 'expenseCreatedBy');
    }
    public function party()
    {
        return $this->belongsTo(Party::class, 'expenseCreatedBy');
    }
}
