<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;
    protected $fillable = ['ledger_id','amount', 'loss_quantity', 'rate', 'parent_id',];
    protected $hidden = ['deleted_at', 'updated_at'];
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }
    public function adjustments()
    {
        return $this->hasMany(Expense::class, 'parent_id');
    }

   public const EXPENSE_CREATED = 'Expense created successfully';
   public const EXPENSE_UPDATED = 'Expense updated successfully';
   public const EXPENSE_DELETED = 'Expense deleted successfully';
}
