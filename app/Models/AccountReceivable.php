<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountReceivable extends Model
{
    protected $table='account_receivables';
    use HasFactory;

    protected $hidden = ['updated_at'];
    protected $fillable = [
        'customer_id',
        'category_id',
        'balance',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creditSales()
    {
        return $this->hasMany(CreditSale::class, 'category_id', 'category_id')
            ->where('customer_id', $this->customer_id);
    }

}
