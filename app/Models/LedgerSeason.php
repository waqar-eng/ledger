<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerSeason extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'ledger_seasons';

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'business_id'
    ];
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public static function getActiveSeason(){
       return self::where('status','active')->first();
    }

    public const LEDGER_SEASONS_RETRIEVED = 'Seasons retrieved successfully';
    public const LEDGER_SEASON_CREATED    = 'Season created successfully';
    public const LEDGER_SEASON_RETRIEVED  = 'Season retrieved successfully';
    public const LEDGER_SEASON_UPDATED    = 'Season updated successfully';
    public const LEDGER_SEASON_DELETED    = 'Season deleted successfully';
    public const LEDGER_SEASON_SUMMARY    = 'Season summary retrived successfully';
    public const NO_ACTIVE_SEASON    = 'No active season found. Please activate or create a season before creating ledger.';

}
