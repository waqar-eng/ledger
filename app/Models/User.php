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
        'user_type',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
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

    public const LOGIN_SUCCESS="Login successfully";
    public const LOGIN_ERROR="Login un-successfully ";
    public const USER_CREATED='User created successfully';
    public const USER_UPDATED='User updated successfully';
    public const USER_DELETED="User deleted successfully";
    public const USERS_FETCHED="User details fetched successfully";
    public const USERS_FETCHED_ERROR="Error fetching user details";
}
