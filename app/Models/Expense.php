<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['ledger_id','amount','description'];
    public function ledger()
{
    return $this->belongsTo(Ledger::class);
}

   public const EXPENSE_CREATED = 'Expense created successfully';
   public const EXPENSE_UPDATED = 'Expense updated successfully';
   public const EXPENSE_DELETED = 'Expense deleted successfully';
}
