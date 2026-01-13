<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;
    protected $fillable = ['ledger_id','quantity', 'rate', 'amount','parent_id'];

    protected $hidden = ['deleted_at', 'updated_at'];
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
        return $this->hasMany(Sale::class, 'parent_id');
    }

   public const SALE_CREATED = 'Sale created successfully';
   public const SALE_UPDATED = 'Sale updated successfully';
   public const SALE_DELETED = 'Sale deleted successfully';
}
