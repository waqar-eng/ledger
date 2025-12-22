<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['ledger_id','amount', 'loss_quantity', 'rate', 'customer_id', 'category_id',];
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

   public const EXPENSE_CREATED = 'Expense created successfully';
   public const EXPENSE_UPDATED = 'Expense updated successfully';
   public const EXPENSE_DELETED = 'Expense deleted successfully';
}
