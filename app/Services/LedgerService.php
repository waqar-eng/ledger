<?php

namespace App\Services;

use App\Repositories\Interfaces\LedgerRepositoryInterface;
use App\Services\Interfaces\LedgerServiceInterface;
use App\Models\Ledger;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Purchase;
use GuzzleHttp\Psr7\Request;

class LedgerService extends BaseService implements LedgerServiceInterface
{
    public function __construct(LedgerRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function all()
    {
        return Ledger::with('customer')->get();
    }

    public function create($request)
    {

        //Step 1: Get the previous total amount from last valid ledger
        $latestLedger = Ledger::latest()->first();

        $previousTotal = $latestLedger?->total_amount ?? 0;
        $amount = $request['amount'];

        //Step 2: Calculate new total amount based on type
        $newTotal = match ($request['type']) {
            'credit' => $previousTotal + $amount,
            'debit'  => $previousTotal - $amount,
            default  => $previousTotal,
        };
        $newTotal;

        $request['total_amount'] = $newTotal;

        $ledger = Ledger::create($request);


        return $ledger;
    }
}
