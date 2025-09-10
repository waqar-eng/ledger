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
        $perPage = $filters['per_page'] ?? AppConstants::DEFAULT_PER_PAGE;

        [$start_date, $end_date] = $this->parseDates($filters['start_date'] ?? '', $filters['end_date'] ?? '');

        $query = $this->buildQuery($filters, $start_date, $end_date);

        $allData = (clone $query)->get();
        $paginated = $query->paginate($perPage);
        $totals = $this->calculateTotals($allData);

        return [
            'pagination' => $paginated,
            'totals' => $totals
        ];
    }
    private function parseDates($start, $end): array
    {
        if (!empty($start) && !empty($end)) {
            $start = Carbon::parse($start)->startOfDay();
            $end = Carbon::parse($end)->endOfDay();
        } else {
            $start = $end = null;
        }

        return [$start, $end];
    }

    private function buildQuery(array $filters, $start_date, $end_date)
    {
        return Ledger::with('customer')
            ->when($start_date && $end_date, fn($q) => $this->applyDateFilters($q, $start_date, $end_date))
            ->when(!empty($filters['customer_id']), fn($q) => $q->where('customer_id', $filters['customer_id']))
            ->when(!empty($filters['search_term']), fn($q) => $q->where('description', 'like', '%' . $filters['search_term'] . '%'))
            ->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['ledger_type']), fn($q) => $q->where('ledger_type', $filters['ledger_type']))
            ->orderByDesc('id');
    }

    private function applyDateFilters($query, $start_date, $end_date)
    {
        return $query->whereBetween('created_at', [$start_date, $end_date]);
    }

    private function calculateTotals($data)
    {
        return [
            'sale' => $data->where('ledger_type', 'sale')->sum('amount'),
            'purchase' => $data->where('ledger_type', 'purchase')->sum('amount'),
            'expense' => $data->where('ledger_type', 'expense')->sum('amount'),
            'total_amount' => optional($data->first())->total_amount ?? 0,
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

    public function getDashboardSummary(): array
    {
        $now = Carbon::now();

        $daily = Ledger::whereDate('created_at', $now->toDateString())->get();
        $monthly = Ledger::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->get();
        $yearly = Ledger::whereYear('created_at', $now->year)->get();

        $formatTotals = fn($collection) => [
            'sales' => $collection->where('ledger_type', 'sale')->sum('amount'),
            'expenses' => $collection->where('ledger_type', 'expense')->sum('amount'),
            'purchases' => $collection->where('ledger_type', 'purchase')->sum('amount'),
        ];

        return [
             'dashboard_summary' => [
                'daily' => $formatTotals($daily),
                'monthly' => $formatTotals($monthly),
                'yearly' => $formatTotals($yearly),
             ]
        ];
    }


}
