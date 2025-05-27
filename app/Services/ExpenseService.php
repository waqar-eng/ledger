<?php

namespace App\Services;

use App\Repositories\Interfaces\ExpenseRepositoryInterface;
use App\Services\Interfaces\ExpenseServiceInterface;
use App\Models\Expense;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Ledger;

class ExpenseService extends BaseService implements ExpenseServiceInterface
{
    protected $expenseRepositoryInterface;
    public function __construct(ExpenseRepositoryInterface $expenseRepositoryInterface)
    {
        parent::__construct($expenseRepositoryInterface);
        $this->expenseRepositoryInterface = $expenseRepositoryInterface;
    }

    public function all(array $filters = [])
    {


        $query = Expense::with(['ledger', 'ledger.customer']);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {

            $start = Carbon::parse($filters['start_date'])->toDateString();
            $end = Carbon::parse($filters['end_date'])->toDateString();



            $query->whereHas('ledger', function ($q) use ($start, $end) {

                $q->whereDate('date', '>=', $start)
                    ->whereDate('date', '<=', $end);
            });
        }

        $results = $query->get();


        return $results;
    }

    
}
