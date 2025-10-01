<?php

namespace App\Services;

use App\AppEnum;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Ledger;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use Carbon\Carbon;

class ReportService
{
    public function generateReport($request)
    {
        
        $userId = $request['user_id'] ?? null;
        $categoryId = $request['category_id'] ?? null;
        $dateFrom = $request['from'] ?? null;
        $dateTo   = $request['to'] ?? null;
        $dateFrom = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $dateTo   = $dateTo   ? Carbon::parse($dateTo)->endOfDay()   : null;

        $applyFilters = function ($query) use ($userId, $categoryId, $dateFrom, $dateTo) {
            if ($userId) {
                $query->where('user_id', $userId);
            }
            if ($categoryId) {
                // Purchases & Sales filter by Ledger → category_id
                $query->whereHas('ledger', function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }
            if ($dateFrom && $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            } elseif ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            } elseif ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }
        };

        // Investments
        $investments = Investment::where('type', AppEnum::Investment->value)->when(true, $applyFilters)->sum('amount');

        // Purchases
        $purchases = Purchase::when(true, $applyFilters)->sum('amount');

        // Sales
        $sales = Sale::when(true, $applyFilters)->sum('amount');

        // Expenses
        $expenses = Expense::when(true, $applyFilters)->sum('amount');

        // Withdrawals
        $withdrawals = Investment::where('type', AppEnum::Withdraw->value)->when(true, $applyFilters)->sum('amount');

        // Available stocks (filter category if requested)
        $stocksQuery = Stock::latest('id');
        if ($categoryId) {
            $stocksQuery->where('category_id', $categoryId);
        }

        $stocks = $stocksQuery
            ->get()
            ->groupBy('category_id')
            ->map(fn($rows) => $rows->sum('total_quantity'));

        // Profit calculations
        $grossProfit = $sales - $purchases;
        $netProfit   = $grossProfit - $expenses;

        // Equity position = investments - withdrawals + net profit
        $equity = $investments - $withdrawals + $netProfit;

        return [
            'totals' => [
                'investments'  => $investments,
                'withdrawals'  => $withdrawals,
                'purchases'    => $purchases,
                'sales'        => $sales,
                'expenses'     => $expenses,
                'gross_profit' => $grossProfit,
                'net_profit'   => $netProfit,
                'equity'       => $equity,
            ],
            'stocks' => $stocks,
        ];
    }
}
