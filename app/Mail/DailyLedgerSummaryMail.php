<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyLedgerSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $date,
        public $expenses,
        public $sales,
        public $purchases
    ) {}

    public function build()
    {
        return $this
            ->subject('Daily Ledger Summary - ' . $this->date)
            ->view('emails.daily-ledger-summary')
            ->with([
                'expensesTotal' => $this->expenses->sum('amount'),
                'salesTotal' => $this->sales->sum('amount'),
                'purchasesTotal' => $this->purchases->sum('amount'),
            ]);
    }
}
