<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class payment extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'ledger_id',
        'parent_id',
        'user_id',
        'category_id',
        'amount',
        'paid_amount',
        'remaining_amount',
        'direction',
    ];

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function adjustments()
    {
        return $this->hasMany(payment::class, 'parent_id');
    }
}
