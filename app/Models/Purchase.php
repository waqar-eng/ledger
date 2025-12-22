<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = ['ledger_id', 'actual_quantity', 'predicted_quantity', 'status',
    'moisture', 'rate', 'amount', 'payment_method', 'paid_amount', 'remaining_amount', 'customer_id', 'category_id'
    ];
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

   public const PURCHASE_CREATED = 'Purchase created successfully';
   public const PURCHASE_UPDATED = 'Purchase updated successfully';
   public const PURCHASE_DELETED = 'Purchase deleted successfully';
}
