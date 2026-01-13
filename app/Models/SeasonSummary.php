<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonSummary extends Model
{
    protected $fillable = [
        'season_id',
        'total_sales',
        'total_purchases',
        'total_expenses',
        'total_investment',
        'profit',
        'investor_breakdown',
        'remaining_stock',
        'total_payables',
        'total_receivables',
        'cannot_close_reason',
    ];
    protected $casts = [
        'remaining_stock'    => 'array',
        'total_payables'     => 'array',
        'total_receivables'  => 'array',
        'investor_breakdown' => 'array',
    ];

     public function season()
    {
        return $this->belongsTo(LedgerSeason::class, 'season_id');
    }
}
