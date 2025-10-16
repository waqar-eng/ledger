<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'customer_id',
        'ledger_id',
        'remaining_amount',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function accountsReceivable()
    {
        return $this->belongsTo(AccountReceivable::class, 'category_id', 'category_id')
            ->where('customer_id', $this->customer_id);
    }
}
