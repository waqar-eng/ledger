<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'address',
        'type',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
        'updated_at'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->useLogName('user')
            ->setDescriptionForEvent(fn(string $eventName) => "User has been {$eventName}");
    }
    public function sales()
    {
        return $this->hasManyThrough(
            Sale::class,
            Ledger::class,
            'user_id',    // Foreign key on ledgers table
            'ledger_id',  // Foreign key on sales table
            'id',         // Local key on users table
            'id'          // Local key on ledgers table
        );
    }

    public function purchases()
    {
        return $this->hasManyThrough(
            Purchase::class,
            Ledger::class,
            'user_id',
            'ledger_id',
            'id',
            'id'
        );
    }

    public function expenses()
    {
        return $this->hasManyThrough(
            Expense::class,
            Ledger::class,
            'user_id',
            'ledger_id',
            'id',
            'id'
        );
    }
    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }



    public function accountReceivables()
    {
        return $this->hasMany(AccountReceivable::class, 'user_id');
    }
    public function accountPayables()
    {
        return $this->hasMany(AccountPayable::class, 'user_id');
    }

    public const CUSTOMER_CREATED ='Customer created successfully';
    public const CUSTOMER_UPDATED ='Customer updated successfully';
    public const CUSTOMER_DELETED ='Customer deleted successfully';

    public const LOGIN_SUCCESS="Login successfully";
    public const LOGIN_ERROR="Login un-successfully ";
    public const USER_CREATED='User created successfully';
    public const USER_UPDATED='User updated successfully';
    public const USER_DELETED="User deleted successfully";
    public const USERS_FETCHED="User details fetched successfully";
    public const USERS_FETCHED_ERROR="Error fetching user details";
}
