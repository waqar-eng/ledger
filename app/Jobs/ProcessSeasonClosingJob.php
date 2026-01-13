<?php

namespace App\Jobs;

use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Investment;
use App\Models\Ledger;
use App\Models\SeasonSummary;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSeasonClosingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $season;

    public function __construct($season)
    {
        $this->season = $season;
    }

    public function handle()
    {
        $season = $this->season;
        $start = $season->start_date;
        $end   = $season->end_date;

        // 1. Fetch all ledgers inside date range
        $ledgers = Ledger::whereBetween('date', [$start, $end])->get();

        // 2. Calculate sales, purchases, expenses
        $totalSales = $ledgers->where('ledger_type', 'sale')->sum('amount');
        $totalPurchases = $ledgers->where('ledger_type', 'purchase')->sum('amount');
        $totalExpenses = $ledgers->whereIn('ledger_type', ['expense', 'moisture_loss'])->sum('amount');
        $totalInvestment = $ledgers->where('ledger_type', 'investment')->sum('amount');

        // 3. Investments (assuming investment table has created_at)
        $investments = Investment::with('user')->where('type', 'investment')->whereBetween('date', [$start, $end])->get();
        $profit = $totalSales - ($totalPurchases + $totalExpenses);
        $investorProfit = [];
        foreach ($investments->groupBy('user_id') as $userId => $userInvests) {
            $userTotal = $userInvests->sum('amount');
            $share = $totalInvestment > 0 ? ($userTotal / $totalInvestment) : 0;
            $userName = $userInvests->first()->user?->name ?? 'Unknown';
            $profitAmount = $profit * $share;
            $investorProfit[] = [
                'user_id'=>$userId,
                'username'   => $userName,
                'investment' => $userTotal,
                'profit_share' => $share*100,
                'profit_amount'    => round($profitAmount, 2),
            ];
        }
        // 4. Profit calculation
        $start = Carbon::parse($start)->startOfDay();
        $end   = Carbon::parse($end)->endOfDay();
        $remainingStock = Stock::with('category')->select('category_id', 'total_quantity')
        ->whereBetween('created_at', [$start, $end])
        ->get()->filter(fn($p) => $p->total_quantity != 0);

        $payables = AccountPayable::with('customer', 'category')->select('category_id', 'customer_id', 'balance')
        ->whereBetween('created_at', [$start, $end])
        ->get()->filter(fn($p) => $p->balance != 0);

        
        $receivables = AccountReceivable::with('customer', 'category')->select('category_id', 'customer_id', 'balance')
        ->whereBetween('created_at', [$start, $end])
        ->get()->filter(fn($p) => $p->balance != 0);

        // 5. Investor-wise profit distribution
         $cannotCloseReason = collect([
            $remainingStock->count() ? 'Stock not zero' : null,
            $payables->count()       ? 'Account Payables exist' : null,
            $receivables->count()     ? 'Account Receivables exist' : null,
        ])->filter()->values()->toArray();

        $cannotClose = count($cannotCloseReason) > 0;

        // 6. Save results (create another table: season_summaries)
        SeasonSummary::updateOrCreate(
            ['season_id' => $season->id],
            [
                'total_sales' => $totalSales,
                'total_purchases' => $totalPurchases,
                'total_expenses' => $totalExpenses,
                'total_investment' => $totalInvestment,
                'profit' => $profit,
                
                'remaining_stock' => json_encode($remainingStock->values()),
                'total_payables' => json_encode($payables->values()),
                'total_receivables' => json_encode($receivables->values()),
                'investor_breakdown' => json_encode($investorProfit),

                'cannot_close_reason' => $cannotClose ? implode(', ', $cannotCloseReason) : "Congrat! Season is completed",
            ]
        );
    }
}
