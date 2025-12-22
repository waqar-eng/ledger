<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class payment extends Model
{
    protected $fillable = [
        'ledger_id',
        'customer_id',
        'category_id',
        'amount',
        'paid_amount',
        'remaining_amount',
        'direction',
    ];

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
