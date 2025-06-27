<?php

namespace App\Services;

use App\AppEnum;
use App\Constants\AppConstants;
use App\Repositories\Interfaces\LedgerRepositoryInterface;
use App\Services\Interfaces\LedgerServiceInterface;
use App\Models\Ledger;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Purchase;
use GuzzleHttp\Psr7\Request;
use Illuminate\Validation\ValidationException;

class LedgerService extends BaseService implements LedgerServiceInterface
{
    public function __construct(LedgerRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
    
    public function findAll(array $filters)
    {
        $per_page = $filters['per_page']?? AppConstants::DEFAULT_PER_PAGE;
        $start_date = $filters['start_date']??'';
        $end_date = $filters['end_date']??'';
        $customer_id = $filters['customer_id']??'';
        $search_term = $filters['search_term']??'';
        $type = $filters['type']??'';
        $ledger_type = $filters['ledger_type']??'';
        if (!empty($start_date) && !empty($end_date)) {
            $start_date = Carbon::parse($start_date)->startOfDay();
            $end_date = Carbon::parse($end_date)->endOfDay();
        }
           $query = Ledger::with('customer')
             ->when(!empty($start_date) && !empty($end_date), function ($q) use ($start_date, $end_date) {
              $q->whereBetween('created_at', [$start_date, $end_date]);
        })
        ->when(!empty($customer_id), fn($q) => $q->where('customer_id', $customer_id))
        ->when(!empty($search_term), fn($q) => $q->where('description', 'like', '%' . $search_term . '%'))
        ->when(!empty($type), fn($q) => $q->where('type', $type))
        ->when(!empty($ledger_type), fn($q) => $q->where('ledger_type', $ledger_type))
        ->orderByDesc('id');

        $allData = (clone $query)->get();  

        $paginated = $query->paginate($per_page);  

        $totals = [
            'sale' => $allData->where('ledger_type', 'sale')->sum('amount'),
            'purchase' => $allData->where('ledger_type', 'purchase')->sum('amount'),
            'expense' => $allData->where('ledger_type', 'expense')->sum('amount'),
            'total_amount' => optional($allData->first())->total_amount ?? 0,
        ];

         return [
            'pagination' => $paginated,
            'totals' => $totals
        ];
    }



    public function create($request)
    {

        //Step 1: Get the previous total amount from last valid ledger
        $latestLedger = Ledger::latest()->first();

        $previousTotal = $latestLedger?->total_amount ?? 0;
        $amount = $request['amount'];

       // Step 2: Determine type based on ledger_type
        $type = match ($request['ledger_type']) {
            'purchase', 'expense', 'withdraw', 'repayment', 'other' => AppEnum::Debit->value,
            'sale', 'investment' => AppEnum::Credit->value,

            default => throw ValidationException::withMessages([
                'ledger_type' => ['Invalid ledger type.']
            ])
        };

        // Step 3: Calculate new total
        $newTotal = $type === AppEnum::Credit->value
            ? $previousTotal + $amount
            : $previousTotal - $amount;

        // Step 4: Check for negative balance
        if ($newTotal < 0) {
            throw ValidationException::withMessages([
                'amount' => [Ledger::LOW_BALANCE_ERROR],
            ]);
        }

        // Step 5: Set derived fields in request data
        $request['type'] = $type;
        $request['total_amount'] = $newTotal;
        
        $ledger = Ledger::create($request);

        switch($request['ledger_type']){
            case 'sale':
                Sale::create(['ledger_id' => $ledger->id]);
                break;
            case 'expense':
                Expense::create(['ledger_id' =>$ledger->id]);
                break;
            case 'purchase':
                Purchase::create(['ledger_id' => $ledger->id]);
                break;
        }
        return $ledger;
    }
}
