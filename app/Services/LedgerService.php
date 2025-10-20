<?php

namespace App\Services;

use App\AppEnum;
use App\Constants\AppConstants;
use App\Jobs\ProcessLedgerJob;
use App\Jobs\RecalculateTotalsJob;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\CreditPurchase;
use App\Models\CreditSale;
use App\Repositories\Interfaces\LedgerRepositoryInterface;
use App\Services\Interfaces\LedgerServiceInterface;
use App\Models\Ledger;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Stock;
use App\Services\Helpers\LedgerHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LedgerService extends BaseService implements LedgerServiceInterface
{
    public function __construct(
    LedgerRepositoryInterface $repository, 
    private StockService $stock_service,
    private AccountReceiveableService $account_receiveable_service,
    private AccountPayableService $account_payable_service,
    private ReportService $report_service,
    private PurchaseService $purchase_service,
    )
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
            ->when(!empty($filters['is_credit_sale']), function ($q) {
                $q->where(function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('ledger_type', 'sale')
                            ->whereIn('payment_type', [AppEnum::Credit, AppEnum::Partial]);
                    })
                    ->orWhere(function ($sub) {
                        $sub->where('ledger_type', 'receive-payment');
                    });
                });
            })
            ->when(!empty($filters['is_credit_purchase']), function ($q) {
                $q->where(function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('ledger_type', 'purchase')
                            ->whereIn('payment_type', [AppEnum::Credit, AppEnum::Partial]);
                    })
                    ->orWhere(function ($sub) {
                        $sub->where('ledger_type', 'payment');
                    });
                });
            })
            ->orderByDesc('id');
    }

    public function applyDateFilters($query, $start_date, $end_date)
    {
        return $query->whereBetween('created_at', [$start_date, $end_date]);
    }

    private function calculateTotals($data, $filters)
    {
        // Helper flags
        $isCreditPurchase = !empty($filters['is_credit_purchase']);
        $isCreditSale     = !empty($filters['is_credit_sale']);
        $hasCustomer      = !empty($filters['customer_id']);
        $hasCategory      = !empty($filters['category_id']);

        $totals = [
            'sale'       => $data->where('ledger_type', AppEnum::Sale)->sum('amount') ?? 0,
            'purchase'   => $data->where('ledger_type', AppEnum::Purchase)->sum('amount') ?? 0,
            'expense'    => $data->where('ledger_type', AppEnum::Expense)->sum('amount') ?? 0,
            'investment' => $data->where('ledger_type', AppEnum::Investment)->sum('amount') ?? 0,
            'withdrawal' => $data->where('ledger_type', AppEnum::Withdraw)->sum('amount') ?? 0,
            'payment'    => $isCreditPurchase ? $data->where('ledger_type', AppEnum::Payment)->sum('paid_amount') : 0,
            'receive_payment' => $isCreditSale ? $data->where('ledger_type', AppEnum::ReceivePayment)->sum('paid_amount') : 0,
        ];

        $total_amount = optional($data->first())->total_amount ?? 0;
        $total_paid     = $totals['payment'] ?? 0;
        $total_received = $totals['receive_payment'] ?? 0;

        // 1️⃣ User specific total
        if (!empty($filters['user_id'])) {
            $total_amount = $totals['investment'] - $totals['withdrawal'];
        }

        // 2️⃣ Specific ledger type totals
        elseif (!empty($filters['ledger_type'])) {
            $type = $filters['ledger_type'];
            if (in_array($type, ['withdraw', 'investment']) && isset($totals[$type])) {
                $total_amount = $totals[$type];
            }
        }

        // 3️⃣ Category & Customer totals (Accounts Payable/Receivable)
        elseif ($hasCategory) {
            $query = $isCreditPurchase ? AccountPayable::query() : AccountReceivable::query();
            $query->where('category_id', $filters['category_id']);

            if ($hasCustomer) {
                $query->where('customer_id', $filters['customer_id']);
            }

            $total_amount = $query->sum('balance');
            if($isCreditPurchase)
            $total_paid = $data->sum('paid_amount');
            if($isCreditSale)
            $total_received = $data->sum('paid_amount');
        }

        // 4️⃣ Customer totals only
        elseif ($hasCustomer) {
            $query = $isCreditPurchase ? AccountPayable::query() : AccountReceivable::query();
            $total_amount = $query->where('customer_id', $filters['customer_id'])->sum('balance');
            if($isCreditPurchase)
            $total_paid = $data->sum('paid_amount');
            if($isCreditSale)
            $total_received = $data->sum('paid_amount');
        }

        // 5️⃣ All Payable or Receivable totals
        elseif ($isCreditPurchase) {
            $total_amount = AccountPayable::sum('balance');
            $total_paid = $data->sum('paid_amount');
        } elseif ($isCreditSale) {
            $total_amount = AccountReceivable::sum('balance');
            $total_received = $data->sum('paid_amount');
        }

        return array_merge($totals, [
            'total_amount' => $total_amount,
            'total_received' => $total_received,
            'total_paid' => $total_paid,
        ]);
    }


    public static function getLedgerType($ledger_type)
    {
        return match ($ledger_type) {
            'purchase', 'expense', 'withdraw', 'moisture_loss', 'other','payment' => AppEnum::Debit->value,
            'sale', 'investment','receive-payment' => AppEnum::Credit->value,

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
        $ledgerType = $request['ledger_type'];
        $type = self::getLedgerType($ledgerType);

        $newAmount = ($ledgerType == AppEnum::Investment->value 
        || $ledgerType == AppEnum::Expense->value 
        || $ledgerType == AppEnum::Withdraw->value) 
        ? $request['amount'] : $request['paid_amount'];

        $newTotal = $type === AppEnum::Credit->value
            ? $previousTotal + $newAmount
            : $previousTotal - $newAmount;

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
        ProcessLedgerJob::dispatch('create', null, $request);
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
        ProcessLedgerJob::dispatch('update', $id, $request);
    }
    private function handlePaymentUpdate(
    Ledger $ledger,
    array $request,
    string $serviceType // 'receivable' or 'payable'
    ): void {
        $oldPaidAmount = $ledger->paid_amount ?? 0;
        $newPaidAmount = $request['paid_amount'] ?? 0;
        $difference = $newPaidAmount - $oldPaidAmount;

        // Update ledger first
        $ledger->update($request);

        // Resolve target service dynamically
        $service = $serviceType === 'receivable'
            ? $this->account_receiveable_service
            : $this->account_payable_service;

        // Apply logic only if there’s a difference
        if ($difference === 0) {
            return;
        }

        if ($difference > 0) {
            // Extra payment — reduce receivable/payable
            $service->reduce([
                'customer_id' => $request['customer_id'],
                'category_id' => $request['category_id'],
                'paid_amount' => $difference,
            ]);
        } else {
            // Payment decreased — restore back
            $service->restore(
                $request['customer_id'],
                abs($difference),
                $request['category_id']
            );
        }
    }
    public function billNumber()
    {
        $count = Ledger::count() ?? 0;
        return $count + 1;
    }

    public function report($request)
    {
        $this->report_service->generateReport($request);
    }

    public function delete($id)
    {
        ProcessLedgerJob::dispatch('delete', $id);
    }

    // handlers
    public function handleCreate(array $request)
    {
        return DB::transaction(function () use ($request) {
        //Step 1: Get the previous total amount from last valid ledger
        $amount = $request['amount'] ?? 0;
        $lastQuantity = $this->stock_service->checkStock($request);
        $typeAndNewtotal = self::ledgerNewTotalAndType($request);
        $request['payment_type'] = LedgerHelper::resolvePaymentType($request);
        // Set derived fields in request data
        $request['type'] = $typeAndNewtotal['type'];
        $request['total_amount'] = $typeAndNewtotal['newTotal'];
        if(!$amount){
            $request['rate'] = null;
            $request['paid_amount'] = null;
            $request['remaining_amount'] = null;
        }
        $ledger = Ledger::create($request);
        $request['ledger_id'] = $ledger->id;

        switch ($request['ledger_type']) {
            case 'sale':
                Sale::create($request);
                if(in_array($request['payment_type'], [AppEnum::Credit->value, AppEnum::Partial->value])) {
                    CreditSale::create($request);
                    $this->account_receiveable_service->updateOrInsert($request);
                }
                $this->stock_service->updateStock($request, $lastQuantity);
                break;
            case 'expense':
                Expense::create($request);
                break;
            case 'moisture_loss':
                $request['loss_quantity'] = $request['quantity'];
                Expense::create($request);
                $this->stock_service->updateStock($request, $lastQuantity);
                break;
            case 'purchase':
                $this->purchase_service->createWithMoisture($request);
                if(in_array($request['payment_type'], [AppEnum::Credit->value, AppEnum::Partial->value]) && $amount>0) {
                    CreditPurchase::create($request);
                    $this->account_payable_service->updateOrInsert($request);
                }
                $this->stock_service->updateStock($request, $lastQuantity);
                break;
            case 'receive-payment':
                $this->account_receiveable_service->reduce($request);
                break;
            case 'payment':
                $this->account_payable_service->reduce($request);
                break;
            case 'investment':
            case 'withdraw':
                $newInvestment = self::investmentNewTotal($request);
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
      });
    }

    public function handleUpdate(array $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $ledger = self::find($id);
            if ($ledger->ledger_type == $request['ledger_type']) {
                $request['payment_type'] = LedgerHelper::resolvePaymentType($request);
                $typeAndNewTotal = self::ledgerNewTotalAndType($request, $ledger->id);
                $request['type'] = $typeAndNewTotal['type'] ?? 'investment';
                $request['total_amount'] = $typeAndNewTotal['newTotal'] ?? 0;
                // Update ledger itself
                if($request['ledger_type']!=='moisture_loss' && $request['ledger_type']!=='purchase' && $request['ledger_type']!=='sale' && $request['ledger_type']!=='receive-payment' && $request['ledger_type']!=='payment')
                $ledger->update($request);
                $newInvestment = self::investmentNewTotal($request, $ledger->investment ? $ledger->investment->id : null);

                // Update relation based on ledger_type
                switch ($request['ledger_type']) {
                    case 'sale':
                        LedgerHelper::adjustStockOnUpdate($ledger, $request);
                        // Update sale relation
                        $ledger->sale()->updateOrCreate(['ledger_id' => $ledger->id], $request);
                        if(in_array($request['payment_type'], [AppEnum::Credit->value, AppEnum::Partial->value])) {
                            $ledger->creditSale()->updateOrCreate(['ledger_id' => $ledger->id], $request);
                            $oldCustomerId = $ledger->customer_id;
                            $this->account_receiveable_service->updateOrInsert($request, true, $oldCustomerId);
                        }
                        // Finally, update the ledger record
                        $ledger->update($request);
                        break;
                    case 'purchase':
                        LedgerHelper::adjustStockOnUpdate($ledger, $request);
                        $this->purchase_service->updateWithMoisture($ledger->id, $request);
                        if(in_array($request['payment_type'], [AppEnum::Credit->value, AppEnum::Partial->value]) && $request['amount']>0) {
                            $ledger->creditPurchase()->updateOrCreate(['ledger_id' => $ledger->id], $request);
                            $this->account_payable_service->updateOrInsert($request, true);
                        }
                        $ledger->update($request);
                        break;
                    case 'expense':
                        $ledger->expense()->updateOrCreate(['ledger_id' => $ledger->id], $request);
                        break;
                    case 'moisture_loss':
                        $request['loss_quantity'] = $request['quantity'];
                        LedgerHelper::adjustStockOnUpdate($ledger, $request);
                        $ledger->update($request);
                        $ledger->expense()->updateOrCreate(['ledger_id' => $ledger->id], $request);
                        break;
                    case 'receive-payment':
                        $this->handlePaymentUpdate($ledger, $request, 'receivable');
                        break;

                    case 'payment':
                        $this->handlePaymentUpdate($ledger, $request, 'payable');
                        break;
                    case 'investment':
                    case 'withdraw':
                        $request['total_amount'] = $newInvestment;
                        $request['type']         = $request['ledger_type'];
                        Investment::updateOrCreate(['ledger_id' => $ledger->id], $request);
                        break;
                }
                $ledger= $ledger->fresh();                
                RecalculateTotalsJob::dispatch(
                    \App\Models\Ledger::class,
                    'type',
                    $ledger->id,
                    [],
                    $ledger->total_amount
                );
                if (in_array($ledger->ledger_type, ['investment', 'withdraw'])) {
                            RecalculateTotalsJob::dispatch(
                                \App\Models\Investment::class,
                                'type',
                                $ledger->investment?->id ?? 0,
                                ['user_id' => $ledger->user_id],
                                $newInvestment
                            );
                    }
            }
            return $ledger;
        });
    }

    public function handleDelete(int $id)
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
                    $ledger->creditSale?->delete();
                    $this->account_receiveable_service->updateOrInsert($ledger->toArray(), true);
                    Stock::where('category_id', $ledger->category_id)
                        ->increment('total_quantity', $ledger->quantity);
                    break;

                case 'purchase':
                    $ledger->purchase?->delete();
                    $ledger->creditPurchase?->delete();
                    $this->account_payable_service->updateOrInsert($ledger->toArray(), true);
                    Stock::where('category_id', $ledger->category_id)
                        ->decrement('total_quantity', $ledger->quantity);
                    break;

                case 'moisture_loss':
                    // Restore the previously deducted stock
                    Stock::where('category_id', $ledger->category_id)
                        ->increment('total_quantity', $ledger->quantity);

                    // Delete the linked expense (if any)
                    $ledger->expense?->delete();
                    break;
                case 'expense':
                    $ledger->expense?->delete();
                    break;
                case 'receive-payment':
                    // 🧾 Reverse the receive-payment impact
                    $customerId = $ledger->customer_id;
                    $paidAmount = $ledger->paid_amount ?? 0;
                    $categoryId = $ledger->category_id;
    
                    if ($paidAmount > 0 && $customerId && $categoryId) {
                        // Call restore() to return this amount to credit sales
                        $this->account_receiveable_service->restore($customerId, $paidAmount, $categoryId);
                    }
                    break;
                case 'payment':
                    $customerId = $ledger->customer_id;
                    $paidAmount = $ledger->paid_amount ?? 0;
                    $categoryId = $ledger->category_id;

                    if ($paidAmount > 0 && $customerId && $categoryId) {
                        // Supplier payment deleted → restore payable balance
                        $this->account_payable_service->restore($customerId, $paidAmount, $categoryId);
                    }    
                    break;
                case 'investment':
                case 'withdraw':
                    $invId = $ledger->investment?->id;
                    $ledger->investment?->delete();
                    // Dispatch recalculation in queue
                    RecalculateTotalsJob::dispatch(
                        \App\Models\Investment::class,
                        'type',
                        $invId ?? 0,
                        ['user_id' => $ledger->user_id]
                    );
                    break;
            }

            $ledger->delete();

            // Dispatch recalculation for ledgers in queue
            RecalculateTotalsJob::dispatch(
                \App\Models\Ledger::class,
                'type',
                $id
            );
            return true;
        });
    }

}
