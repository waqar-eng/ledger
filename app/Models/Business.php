<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;
    //
    protected $hidden = ['updated_at','created_at'];
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];
    public function accounts()
    {
        return $this->hasMany(Account::class, 'business_id');
    }

    public const BUSINESS_CREATED = 'Business created successfully.';
    public const BUSINESS_UPDATED = 'Business updated successfully.';
    public const BUSINESS_DELETED = 'Business deleted successfully.';
}
