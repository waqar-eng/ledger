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
        'total_amount'
       
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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

public const LOW_BALANCE_ERROR= "Insufficient balance to perform debit transaction";

}
