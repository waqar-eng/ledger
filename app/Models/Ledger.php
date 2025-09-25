<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Ledger extends Model
{
        use LogsActivity , SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'description',
        'amount',
        'type',
        'date',
        'customer_id',
        'ledger_type',
        'user_id',
        'ledger_type',
        'total_amount',
        'payment_type',
        'payment_method',
        'paid_amount',
        'remaining_amount',
        'quantity',
        'rate',
        'bill_no'

    ];
    protected $casts = [
        'amount' => 'float',
        'total_amount' => 'float', // if needed
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function investment()
    {
        return $this->hasOne(Investment::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['description', 'amount', 'type', 'date', 'customer_id'])
            ->logOnlyDirty()
            ->useLogName('ledger')
            ->setDescriptionForEvent(fn(string $eventName) => "Ledger entry has been {$eventName}");
    }


    public function sale()
{
    return $this->hasOne(Sale::class);
}

public function purchase()
{
    return $this->hasOne(Purchase::class);
}

public function expense()
{
    return $this->hasOne(Expense::class);
}

public const LOW_BALANCE_ERROR= "Insufficient balance to perform this transaction";

public const LEDGER_CREATED= "Ledger created successfully";
public const LEDGER_UPDATED= "Ledger updated successfully";
public const LEDGER_DELETED= "Ledger deleted successfully";
public const UPDATE_RESTRICTED= "Only latest record be able to edit";
}
