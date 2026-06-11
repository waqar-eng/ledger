<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;
    //
    protected $hidden = ['updated_at'];
    protected $fillable = [
        'name',
        'type',
        'opening_balance',
        'is_active',
    ];

    
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public const ACCOUNT_CREATED = 'Account created successfully.';
    public const ACCOUNT_UPDATED = 'Account updated successfully.';
    public const ACCOUNT_DELETED = 'Account deleted successfully.';
}
