<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['ledger_id','quantity', 'rate', 'amount', 'payment_method', 'paid_amount', 'remaining_amount', 'customer_id', 'category_id', 'status'];

    protected $hidden = ['deleted_at', 'updated_at'];
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

   public const SALE_CREATED = 'Sale created successfully';
   public const SALE_UPDATED = 'Sale updated successfully';
   public const SALE_DELETED = 'Sale deleted successfully';
}
