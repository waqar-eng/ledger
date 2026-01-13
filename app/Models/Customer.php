<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, LogsActivity ,SoftDeletes;

    protected $primaryKey = "id";

    protected $fillable = [
        'name',
        'phone_number',
        'address',
        'email',
        'type',
    ];
    protected $hidden = ['deleted_at', 'updated_at'];
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }



    public function accountReceivables()
    {
        return $this->hasMany(AccountReceivable::class, 'customer_id');
    }
    public function accountPayables()
    {
        return $this->hasMany(AccountPayable::class, 'customer_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone_number', 'address', 'email'])
            ->logOnlyDirty()
            ->useLogName('customer')
            ->setDescriptionForEvent(fn(string $eventName) => "Customer has been {$eventName}");
    }
    public const CUSTOMER_CREATED ='Customer created successfully';
    public const CUSTOMER_UPDATED ='Customer updated successfully';
    public const CUSTOMER_DELETED ='Customer deleted successfully';
}
