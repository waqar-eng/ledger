<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class   LedgerAccounts extends Model
{
    use HasFactory;

    protected $table = 'ledger_accounts';
    protected $hidden = ['updated_at','created_at'];

    protected $fillable = [
        'ledger_id',
        'account_id',
        'amount',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Each split belongs to a ledger entry
    public function ledger()
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    // Each split belongs to an account
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
