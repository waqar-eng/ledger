<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable as QueueQueueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class RecalculateTotalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, QueueQueueable, SerializesModels;

    protected int $fromId;

    public function __construct(int $fromId)
    {
        $this->fromId = $fromId;
    }

   public function handle(): void
{
    // Get all transactions after or equal to the given ID
    $transactions = Transaction::where('id', '>=', $this->fromId)
        ->orderBy('id')
        ->get();

    // Find the last balance before this transaction
    $previousBalance = Transaction::where('id', '<', $this->fromId)
        ->orderBy('id', 'desc')
        ->value('balance') ?? 0;

    $runningBalance = $previousBalance;

    foreach ($transactions as $txn) {
        if ($txn->type === 'credit') {
            $runningBalance += $txn->amount;
        } elseif ($txn->type === 'debit') {
            $runningBalance -= $txn->amount;
        }

        $txn->update(['balance' => $runningBalance]);
    }
}

}
