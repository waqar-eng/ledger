<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Repositories\Interfaces\LedgerRepositoryInterface;
use App\Services\Interfaces\LedgerServiceInterface;
use App\Models\Ledger;
use Carbon\Carbon;
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
        return Ledger::with('customer')
            ->when(!empty($start_date) && !empty($end_date), function ($query) use ($start_date,$end_date) {
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when(!empty($customer_id), function ($query) use ($customer_id) {
                $query->where('customer_id', $customer_id);
            })
            ->when(!empty($search_term), function ($query) use ($search_term) {
                $query->where('description', 'like', '%' . $search_term . '%');
            })
            ->when(!empty($type), function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when(!empty($ledger_type), function ($query) use ($ledger_type) {
                $query->where('ledger_type', $ledger_type);
            })
            ->paginate($per_page);
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
