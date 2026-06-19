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
        'type',
        'date',
        'ledger_type',
        'total_amount',
        'amount',
        'payment_method',
        'bill_no',
        'user_id',
        'category_id',
        'parent_id',
        'ledger_season_id'
    ];
    protected $casts = [
        'amount' => 'float',
        'total_amount' => 'float', // if needed
    ];
    protected $hidden = ['deleted_at', 'updated_at'];
    public function investment()
    {
        return $this->hasOne(Investment::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
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
public function stock()
{
    return $this->hasOne(Stock::class);
}
public function payment()
{
    return $this->hasOne(Payment::class);
}
public function parent()
{
    return $this->belongsTo(Ledger::class, 'parent_id');
}

public function adjustments()
{
    return $this->hasMany(Ledger::class, 'parent_id');
}
public function season()
{
    return $this->belongsTo(LedgerSeason::class, 'ledger_season_id');
}
public function accounts()
{
    return $this->hasMany(LedgerAccounts::class);
}
public const LOW_BALANCE_ERROR= "Insufficient balance to perform this transaction";

public const LEDGER_CREATED= "Ledger created successfully";
public const LEDGER_UPDATED= "Ledger updated successfully";
public const LEDGER_DELETED= "Ledger deleted successfully";
public const UPDATE_RESTRICTED= "Only latest record be able to edit";
public const LEDGER_TYPE_RESTRICTED= "Ledger type is restricted to edit";
public const BILL_NUMBER_SUCCESS= "Bill number created successfully";
public const REPORT_SUCCESS= "Reports fetched successfully";
public const ACCOUNT_RECEIVABlLE_PAYABLE_SUCCESS= "Account receivable payable fetched successfully";
public const LEDGER_DELETION_ERROR= "You can only delete record created within the allowed period.";
public const LEDGER_UPDATION_ERROR= "You can only update record created within the allowed period.";
public const WALK_IN_BUYER_ACCOUNT_ERROR= "Remaining amount is not allowed for walk-in buyer";
public const WALK_IN_SUPPLIER_ACCOUNT_ERROR= "Remaining amount is not allowed for walk-in supplier";
public const ACTIVE_SEASON_SUCCESS= "Active season fetch successfully";
public const ACTIVE_SEASON_FAILED= "No active season found";
}
