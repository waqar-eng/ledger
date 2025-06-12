<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = "id";

    protected $fillable = [
        'name',
        'phone_number',
        'address',
        'email',
    ];

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone_number', 'address', 'email'])
            ->logOnlyDirty()
            ->useLogName('customer')
            ->setDescriptionForEvent(fn(string $eventName) => "Customer has been {$eventName}");
    }
}
