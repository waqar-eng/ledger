<?php

namespace App\Services;

use App\AppEnum;
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
        $amount = $request[AppEnum::Amount->value]; 
        $type = $request['type']; // withdrawal, additional, opening

        //Step 1: Get previous total from Ledger
        $latestLedger = Ledger::latest()->first();
        $previousTotal = $latestLedger?->total_amount ?? 0;
        //Step 2: Determine new total
        $newTotal = match ($type) {
            AppEnum::Withdrawal => $previousTotal - $amount,
            AppEnum::Opening, AppEnum::Additional => $previousTotal + $amount,
            default => $previousTotal,
        };
        $type= $previousTotal>0 ? $type : AppEnum::Opening;
        if ($type == AppEnum::Withdrawal && $newTotal <= 0) {
            return response()->json([
                'message' => 'Insufficient balance for withdrawal'
            ], 400);
        }
        //Step 3: Determine Ledger's Type
        $ledgerType = match ($type) {
            AppEnum::Withdrawal => AppEnum::Debit,
            AppEnum::Opening, AppEnum::Additional => AppEnum::Credit,
            default => AppEnum::Credit,
        };


        //Step 4: Create Investment first
         $commonData = [
            'type' => $type,
            AppEnum::Amount->value => $amount,
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
