<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;
    protected $fillable = ['ledger_id', 'quantity', 'predicted_quantity', 'moisture', 'rate', 'amount', 'parent_id'];
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
        return $this->hasMany(Purchase::class, 'parent_id');
    }

   public const PURCHASE_CREATED = 'Purchase created successfully';
   public const PURCHASE_UPDATED = 'Purchase updated successfully';
   public const PURCHASE_DELETED = 'Purchase deleted successfully';
}
