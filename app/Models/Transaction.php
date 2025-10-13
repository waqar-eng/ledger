<?php

namespace App\Models;

use Defuse\Crypto\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
        use HasFactory, LogsActivity ,SoftDeletes;

    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'category_id',
        'date',
        'description',
        'type',
        'amount',
        'balance'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['date', 'description', 'type', 'amount' ,'balance'])
            ->logOnlyDirty()
            ->useLogName('')
            ->setDescriptionForEvent(fn(string $eventName) => "Transaction has been {$eventName}");
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class );
    }
     public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public const TRANSACTION_CREATED = "Transaction created successfully";
    public const TRANSACTION_UPDATED = "Transaction updated successfully";
    public const TRANSACTION_DELETED = "Transaction deleted successfully";
    public const TRANSACTION_FETCHED = "Transaction fetched successfully";
    public const TRANSACTIONS_FETCHED = "Transactions list fetched successfully";
    public const TRANSACTION_NOT_FOUND = "Transaction not found";
    public const TRANSACTION_ERROR = "An error occurred while processing the transaction";
    public const LOW_BALANCE_ERROR = "inficenit balance";
    public const TRANSACTION_SUMMARY_FETCHED = "Transaction summary fetched successfully";

}
