<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['ledger_id'];
    public function ledger()
{
    return $this->belongsTo(Ledger::class);
}

   public const SALE_CREATED = 'Sale created successfully';
   public const SALE_UPDATED = 'Sale updated successfully';
   public const SALE_DELETED = 'Sale deleted successfully';
}
