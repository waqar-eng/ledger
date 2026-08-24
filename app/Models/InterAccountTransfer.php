<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterAccountTransfer extends Model
{
    protected $fillable = ['from_account_id', 'to_account_id', 'category_id', 'amount', 'date', 'description'];
    protected $hidden = ['deleted_at', 'updated_at'];
    public function transfer()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }
}
