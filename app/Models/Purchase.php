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

}
