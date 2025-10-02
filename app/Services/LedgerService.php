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
use App\Models\Investment;
use App\Models\Purchase;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
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
        $totals = $this->calculateTotals($allData, $filters);

        return [
            'pagination' => $paginated,
            'totals' => $totals
        ];
    }
    public function parseDates($start, $end): array
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
        return Ledger::with(['customer', 'user', 'category'])
            ->when($start_date && $end_date, fn($q) => $this->applyDateFilters($q, $start_date, $end_date))
            ->when(!empty($filters['customer_id']), fn($q) => $q->where('customer_id', $filters['customer_id']))
            ->when(!empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['search_term']), fn($q) => $q->where('description', 'like', '%' . $filters['search_term'] . '%'))
            ->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['ledger_type']), fn($q) => $q->where('ledger_type', $filters['ledger_type']))
            ->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
            ->orderByDesc('id');
    }

    public function applyDateFilters($query, $start_date, $end_date)
    {
        return $query->whereBetween('created_at', [$start_date, $end_date]);
    }

    private function calculateTotals($data, $filters)
    {
        $sale = $data->where('ledger_type', AppEnum::Sale)->sum('amount') ?? 0;
        $purchase = $data->where('ledger_type', AppEnum::Purchase)->sum('amount') ?? 0;
        $expense = $data->where('ledger_type', AppEnum::Expense)->sum('amount') ?? 0;
        $investment = $data->where('ledger_type', AppEnum::Investment)->sum('amount') ?? 0;
        $withdrawal = $data->where('ledger_type', AppEnum::Withdraw)->sum('amount') ?? 0;
        $total_amount = optional($data->first())->total_amount ?? 0;
        // Net total for the user (if user_id provided)
        if (!empty($filters['user_id'])) {
            $total_amount = $investment - $withdrawal;
        } elseif (!empty($filters['ledger_type']) && $filters['ledger_type'] == 'withdraw') {
            $total_amount = $withdrawal;
        } elseif (!empty($filters['ledger_type']) && $filters['ledger_type'] == 'investment') {
            $total_amount = $investment;
        } elseif (!empty($filters['category_id'])) {
            $total_amount = ($sale + $investment) - ($purchase + $expense + $withdrawal);
            if (!empty($filters['customer_id'])) {
                $total_amount = ($purchase && $sale) ? ($purchase - $sale) : ($purchase ? $purchase : $sale);
            }
        }

        return [
            'sale'        => $sale,
            'purchase'    => $purchase,
            'expense'     => $expense,
            'investment'  => $investment,
            'withdrawal'  => $withdrawal,
            'total_amount'   => $total_amount,
        ];
    }

    public static function getLedgerType($ledger_type)
    {
        return match ($ledger_type) {
            'purchase', 'expense', 'withdraw', 'repayment', 'other' => AppEnum::Debit->value,
            'sale', 'investment' => AppEnum::Credit->value,

            default => throw ValidationException::withMessages([
                'ledger_type' => ['Invalid ledger type.']
            ])
        };
    }
    public static function ledgerNewTotalAndType($request, $id = null)
    {
        $query = Ledger::query();
        if ($id) {
            $query->where('id', '<', $id);
        }

        $latestLedger = $query->latest()->first();
        $previousTotal = $latestLedger?->total_amount ?? 0;
        $type = self::getLedgerType($request['ledger_type']);

        $newTotal = $type === AppEnum::Credit->value
            ? $previousTotal + $request['amount']
            : $previousTotal - $request['amount'];

        if ($newTotal < 0) {
            throw ValidationException::withMessages([
                'amount' => [Ledger::LOW_BALANCE_ERROR],
            ]);
        }
        return ['newTotal' => $newTotal, 'type' => $type];
    }
    public static function investmentNewTotal($request, $id = null)
    {
        $query = Investment::where('user_id', $request['user_id']);


        // Exclude current record when updating
        if ($id) {
            $query->where('id', '<', $id);
        }

        $latestInvestment = $query->latest()->first();
        $previousInvestment = $latestInvestment?->total_amount ?? 0;

        // Calculate new total based on amount provided
        $newInvestment = match ($request['ledger_type']) {
            'investment' => $previousInvestment + $request['amount'],
            'withdraw'   => $previousInvestment - $request['amount'],
            default      => $previousInvestment
        };

        if ($newInvestment < 0) {
            throw ValidationException::withMessages([
                'amount' => [Ledger::LOW_BALANCE_ERROR],
            ]);
        }
        return $newInvestment;
    }


    public function create($request)
    {

        //Step 1: Get the previous total amount from last valid ledger
        $amount = $request['amount'];
        $lastQuantity = app(StockService::class)->checkStock($request);
        $typeAndNewtotal = self::ledgerNewTotalAndType($request);

        $newInvestment = self::investmentNewTotal($request);
        // Set derived fields in request data
        $request['type'] = $typeAndNewtotal['type'];
        $request['total_amount'] = $typeAndNewtotal['newTotal'];
        $ledger = Ledger::create($request);
        $request['ledger_id'] = $ledger->id;

        switch ($request['ledger_type']) {
            case 'sale':
                Sale::create($request);
                app(StockService::class)->updateStock($request, $lastQuantity);
                break;
            case 'expense':
                Expense::create($request);
                break;
            case 'purchase':
                app(PurchaseService::class)->createWithMoisture($request);
                app(StockService::class)->updateStock($request, $lastQuantity);
                break;
            case 'investment':
            case 'withdraw':
                Investment::create([
                    'ledger_id' => $ledger->id,
                    'user_id'      => $request['user_id'],
                    'type'         => $request['ledger_type'] ?? 'investment',
                    'amount'       => $amount,
                    'total_amount' =>  $newInvestment,
                    'date'         => $request['date'],
                ]);
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
    public function isLatestLedger($id)
    {
        $lastLedgerId = Ledger::latest('id')->value('id');
        // Step 2: Check if the given id is the last one
        return ($id == $lastLedgerId) ? true : false;
    }
    public function find($id)
    {
        return Ledger::where('id', $id)
            ->with(['investment', 'sale', 'purchase', 'expense'])->first();
    }
    public function update($request, $id)
    {
        $ledger = self::find($id);
        if ($ledger->ledger_type == $request['ledger_type']) {
            $request['user_id'] = $ledger->user_id ?? null;
            $request['customer_id'] = $ledger->customer_id ?? null;
            $typeAndNewTotal = self::ledgerNewTotalAndType($request, $ledger->id);
            $request['type'] = $typeAndNewTotal['type'] ?? 'investment';
            $request['total_amount'] = $typeAndNewTotal['newTotal'] ?? 0;
            // Update ledger itself
            $ledger->update($request);
            $newInvestment = self::investmentNewTotal($request, $ledger->investment ? $ledger->investment->id : null);

            // Update relation based on ledger_type
            switch ($request['ledger_type']) {
                case 'sale':
                    $ledger->sale()->updateOrCreate(['ledger_id' => $ledger->id], $request);
                    break;
                case 'purchase':
                    app(PurchaseService::class)->updateWithMoisture($ledger->id, $request);
                    break;
                case 'expense':
                    $ledger->expense()->updateOrCreate(['ledger_id' => $ledger->id], $request);
                    break;
                case 'investment':
                case 'withdraw':
                    $request['total_amount'] = $newInvestment;
                    $request['type']         = $request['ledger_type'];
                    Investment::updateOrCreate(['ledger_id' => $ledger->id], $request);
                    break;
            }

            return $ledger->fresh();
        }
    }
    public function billNumber()
    {
        $count = Ledger::count() ?? 0;
        return $count + 1;
    }

    public function report($request)
    {
        return app(ReportService::class)->generateReport($request);
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $ledger = Ledger::with(['sale', 'purchase', 'expense', 'investment'])->findOrFail($id);
            if ($ledger->id == Ledger::min('id')) {
                throw new \Exception("You cannot delete the very first ledger (base investment).");
            }
            // Handle related record
            switch ($ledger->ledger_type) {
                case 'sale':
                    $ledger->sale?->delete();
                    Stock::where('category_id', $ledger->category_id)
                        ->increment('total_quantity', $ledger->quantity);
                    break;

                case 'purchase':
                    $ledger->purchase?->delete();
                    Stock::where('category_id', $ledger->category_id)
                        ->decrement('total_quantity', $ledger->quantity);
                    break;

                case 'expense':
                    $ledger->expense?->delete();
                    break;

                case 'investment':
                case 'withdraw':
                    $invId = $ledger->investment?->id;
                    $ledger->investment?->delete();
                    // Adjust totals for investments
                    $this->recalcTotals(
                        Investment::class,
                        'type',       // "investment" or "withdraw"
                        'amount',
                        $invId ?? 0,
                        ['user_id' => $ledger->user_id] // keep user scoped
                    );
                    break;
            }

            $ledger->delete();

            // Adjust totals for subsequent ledgers
            $this->recalcTotals(
                Ledger::class,
                'type',
                'amount',
                $id
            );
            return true;
        });
    }
    private function recalcTotals($model, $typeField, $amountField, $id, $extraWhere = [])
    {
        // Get all records > $id (subsequent)
        $query = $model::where('id', '>', $id)->orderBy('id');
        if (!empty($extraWhere)) {
            $query->where($extraWhere);
        }
        $subsequentRows = $query->get();

        // Get the last valid total before $id
        $previousTotalQuery = $model::where('id', '<', $id);
        if (!empty($extraWhere)) {
            $previousTotalQuery->where($extraWhere);
        }
        $previousTotal = $previousTotalQuery->latest('id')->value('total_amount') ?? 0;

        // ✅ Recalculate each subsequent record
        foreach ($subsequentRows as $row) {
            if ($row->{$typeField} === 'credit' || $row->{$typeField} === 'investment') {
                $previousTotal += $row->{$amountField};
            } elseif ($row->{$typeField} === 'debit' || $row->{$typeField} === 'withdraw') {
                $previousTotal -= $row->{$amountField};
            }

            $row->update(['total_amount' => $previousTotal]);
        }
    }
}
