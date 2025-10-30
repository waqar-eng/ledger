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
    ];

    public const LEDGER_SEASONS_RETRIEVED = 'Seasons retrieved successfully';
    public const LEDGER_SEASON_CREATED    = 'Season created successfully';
    public const LEDGER_SEASON_RETRIEVED  = 'Season retrieved successfully';
    public const LEDGER_SEASON_UPDATED    = 'Season updated successfully';
    public const LEDGER_SEASON_DELETED    = 'Season deleted successfully';

}
