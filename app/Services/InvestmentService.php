<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\Ledger;
use App\Repositories\Interfaces\InvestmentRepositoryInterface;
use App\Repositories\InvestmentRepository;
use App\Services\Interfaces\InvestmentServiceInterface;

class InvestmentService extends BaseService implements InvestmentServiceInterface
{
    protected $investmentRepository;

    public function __construct(InvestmentRepositoryInterface $_investmentRepository)
    {
        parent::__construct($_investmentRepository);
    }
    public function create($request)
    {
        $amount = $request['amount']; 
        $type = $request['type']; // withdrawal, additional, opening

        //Step 1: Get previous total from Ledger
        $latestLedger = Ledger::latest()->first();
        $previousTotal = $latestLedger?->total_amount ?? 0;
        //Step 2: Determine new total
        $newTotal = match ($type) {
            'withdrawal' => $previousTotal - $amount,
            'opening', 'additional' => $previousTotal + $amount,
            default => $previousTotal,
        };
        $type= $previousTotal>0 ? $type : 'opening';
        if ($type == 'withdrawal' && $newTotal <= 0) {
            return response()->json([
                'message' => 'Insufficient balance for withdrawal'
            ], 400);
        }
        //Step 3: Determine Ledger's Type
        $ledgerType = match ($type) {
            'withdrawal' => 'debit',
            'opening', 'additional' => 'credit',
            default => 'credit',
        };


        //Step 4: Create Investment first
         $commonData = [
            'type' => $type,
            'amount' => $amount,
            'user_id' => $request['user_id'],
            'date' => $request['date'],
        ];
        $investment = $this->repository->create($commonData);

        

        //Step 4: Then create Ledger entry
        Ledger::create([
            ...$commonData,
            'type' => $ledgerType,
            'total_amount' => $newTotal,
            'ledgerable_id' => $investment->id,
            'ledgerable_type' => Investment::class,
            'description' => $type ?? '',
            'customer_id' => $request['customer_id'] ?? null,
        ]);

        return $investment;
    }
}
