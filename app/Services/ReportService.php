<?php

namespace App\Services;

use App\AppEnum;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Ledger;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;

class ReportService
{
    public function generateReport()
    {
        // Investments
        $investments = Investment::where('type', AppEnum::Investment->value)->sum('amount');

        // Purchases
        $purchases = Purchase::sum('amount');

        // Sales
        $sales = Sale::sum('amount');

        // Expenses
        $expenses = Expense::sum('amount');

        // Withdrawals
        $withdrawals = Investment::where('type', AppEnum::Withdraw->value)->sum('amount');

        // Available stocks (latest total_quantity per category)
        $stocks = Stock::select('category_id', 'total_quantity')
            ->latest('id')
            ->get()
            ->groupBy('category_id')
            ->map(fn($rows) => $rows->first()->total_quantity);

        // Profit/Loss = Sales - (Purchases + Expenses)
        $profitLoss = $sales - ($purchases + $expenses);

        return [
            'totals' => [
                'investments' => $investments,
                'purchases'   => $purchases,
                'sales'       => $sales,
                'expenses'    => $expenses,
                'withdrawals' => $withdrawals,
                'profit_loss' => $profitLoss,
            ],
            'stocks' => $stocks,
        ];
    }
}
