<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use SoftDeletes;
    protected $fillable = ['ledger_id','user_id', 'type', 'amount', 'total_amount', 'date','category_id', 'parent_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function parent()
    {
        return $this->belongsTo(Investment::class, 'parent_id');
    }

    public function adjustments()
    {
        return $this->hasMany(Investment::class, 'parent_id');
    }

    public const INVESTMENT_SAVE_SUCCESS= "Investment sotred successfully";
    public const INVESTMENT_RETRIVE_SUCCESS= "Investments retrived successfully";
}
