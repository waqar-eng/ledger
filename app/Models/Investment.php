<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    protected $fillable = ['ledger_id','user_id', 'type', 'amount', 'total_amount', 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public const INVESTMENT_SAVE_SUCCESS= "Investment sotred successfully";
    public const INVESTMENT_RETRIVE_SUCCESS= "Investments retrived successfully";
}
