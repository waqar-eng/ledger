<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = ['ledger_id'];

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

   public const PURCHASE_CREATED = 'Purchase created successfully';
   public const PURCHASE_UPDATED = 'Purchase updated successfully';
   public const PURCHASE_DELETED = 'Purchase deleted successfully';
}
