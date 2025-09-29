<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = ['ledger_id', 'category_id', 'total_quantity'];
    protected $hidden = ['deleted_at', 'updated_at'];
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
