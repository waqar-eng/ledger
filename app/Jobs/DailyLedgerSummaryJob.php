<?php

namespace App\Jobs;

use App\Mail\DailyLedgerSummaryMail;
use App\Models\Ledger;
use Illuminate\Support\Facades\Mail;

class DailyLedgerSummaryJob
{
    public function handle(): void
    {
        $start = now()->startOfDay();
        $end   = now()->endOfDay();

        $expenses = Ledger::where('ledger_type', 'expense')
        ->whereBetween('date', [$start, $end])->with('user',)
        ->get();

        $sales = Ledger::where('ledger_type', 'sale')
        ->whereBetween('date', [$start, $end])->with('user',)
        ->get();

        $purchases = Ledger::where('ledger_type', 'purchase')
        ->whereBetween('date', [$start, $end])->with('user',)
        ->get();

        // 🔹 ENABLE AFTER GMAIL APP PASSWORD

        Mail::to(config('mail.admin_email'))
            ->send(new DailyLedgerSummaryMail(
                now()->toDateString(),
                $expenses,
                $sales,
                $purchases
            ));
    }
}
