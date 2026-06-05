<?php

namespace App\Services;

use App\AppEnum;
use App\Constants\AppConstants;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Repositories\Interfaces\LedgerRepositoryInterface;
use App\Services\Interfaces\LedgerServiceInterface;
use App\Models\Ledger;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\LedgerSeason;
use App\Models\payment;
use App\Models\Purchase;
use App\Services\Helpers\LedgerHelper;
use App\Services\Helpers\LedgerReverseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LedgerService extends BaseService implements LedgerServiceInterface
{
    public function __construct(
    LedgerRepositoryInterface $repository,
    private StockService $stock_service,
    private AccountReceiveableService $account_receiveable_service,
    private PaymentService $payment_service,
    private AccountPayableService $account_payable_service,
    private ReportService $report_service,
    private PurchaseService $purchase_service,
    private LedgerHelper $ledgerHelper,
    private LedgerReverseHelper $ledgerReverseHelper
    )
    {
        parent::__construct($repository);
    }

    public function findAll(array $filters)
    {
        $perPage = $filters['per_page'] ?? AppConstants::DEFAULT_PER_PAGE;
        $page = $filters['page'] ?? 1;
        [$start_date, $end_date] = $this->parseDates($filters['start_date'] ?? '', $filters['end_date'] ?? '');

        $query = $this->buildQuery($filters, $start_date, $end_date)->withCount('adjustments');
        
        $paginated = $this->getFilteredTransactionsWithBalance($query , $page , $perPage);
        $allData = (clone $query)->get();
        $totals = $this->calculateTotals($allData, $filters);

        return [
            'pagination' => $paginated,
            'totals' => $totals
        ];
    }
    private function getFilteredTransactionsWithBalance($query, $page, $perPage)
    {
        $transactions = $query->get();
        $sortedForBalance  = $transactions->sortBy('id')->values();

        $runningBalance = 0;
        foreach ($sortedForBalance as $tran) {
            $amount = (float) $tran->amount;
            $runningBalance += $tran->amount;

            $original = $transactions->firstWhere('id', $tran->id);
            if ($original) {
                $original->calculated_balance = $runningBalance;
            }
        }

        $paginated = $transactions->forPage($page,$perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginated,
            $transactions->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
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
        $season_id= $filters['season_id'];
         if (!$season_id) {
            throw new \Exception(LedgerSeason::NO_ACTIVE_SEASON);
        }
        $query= Ledger::with(['user','category','purchase', 'investment', 'expense', 'sale', 'payment' ])
        ->where('ledger_season_id', $season_id)
            ->when($start_date && $end_date, fn($q) => $this->applyDateFilters($q, $start_date, $end_date))
            ->when(!empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['search_term']), fn($q) => $q->where('description', 'like', '%' . $filters['search_term'] . '%'))
            ->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['ledger_type']), fn($q) => $q->where('ledger_type', $filters['ledger_type']))
            ->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
            ->when(!empty($filters['bill_no']), fn($q) => $q->where('bill_no', $filters['bill_no']))

            ->when(!empty($filters['is_credit_sale']), function ($q) {
                $q->where(function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('ledger_type', 'sale');
                    })
                    ->orWhere(function ($sub) {
                        $sub->where('ledger_type', 'receive-payment');
                    });
                });
            })
            ->when(!empty($filters['is_credit_purchase']), function ($q) {
                $q->where(function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('ledger_type', 'purchase');
                    })
                    ->orWhere(function ($sub) {
                        $sub->where('ledger_type', 'payment');
                    });
                });
            })
            ->orderByDesc('id');
        return $query;
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
        $hasUser      = !empty($filters['user_id']);
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

        // 3️⃣ Category & User totals (Accounts Payable/Receivable)
        elseif ($hasCategory) {
            $query = $isCreditPurchase ? AccountPayable::query() : AccountReceivable::query();
            $query->where('category_id', $filters['category_id']);

            if ($hasUser) {
                $query->where('user_id', $filters['user_id']);
            }

            $total_amount = $query->sum('balance');
            if($isCreditPurchase)
            $total_paid = $data->sum('paid_amount');
            if($isCreditSale)
            $total_received = $data->sum('paid_amount');
        }

        // 4️⃣ User totals only
        elseif ($hasUser) {
            $query = $isCreditPurchase ? AccountPayable::query() : AccountReceivable::query();
            $total_amount = $query->where('user_id', $filters['user_id'])->sum('balance');
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
        $season_id= $request['ledger_season_id'];
         if (!$season_id) {
            throw new \Exception(LedgerSeason::NO_ACTIVE_SEASON);
        }
        $query = Ledger::where('ledger_season_id', $season_id);
        if ($id) {
            $query->where('id', '<', $id);
        }

        $latestLedger = $query->latest()->first();
        $previousTotal  = (float) ($latestLedger?->total_amount ?? 0);
        $ledgerType = $request['ledger_type'];
        $type = self::getLedgerType($ledgerType);

        $ledgerAmount = match ($ledgerType) {
            AppEnum::Sale->value,
            AppEnum::Purchase->value,
            AppEnum::Payment->value,
            AppEnum::ReceivePayment->value
                => (float) ($request['paid_amount'] ?? 0),

            default
                => (float) ($request['amount'] ?? 0),
        };
        $newTotal = $type === AppEnum::Credit->value
        ? $previousTotal + $ledgerAmount
        : $previousTotal - $ledgerAmount;

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
        return DB::transaction(function () use ($request) {
        //Step 1: Get the previous total amount from last valid ledger
        $amount = $request['amount'] ?? 0;
        $lastQuantity = $this->stock_service->checkStock($request );
        $typeAndNewtotal = self::ledgerNewTotalAndType($request);
        $request['payment_type'] = LedgerHelper::resolvePaymentType($request);
        // Set derived fields in request data
        $request['type'] = $typeAndNewtotal['type'];
        $request['total_amount'] = $typeAndNewtotal['newTotal'];
        if (in_array($request['ledger_type'],
        [AppEnum::Sale->value, AppEnum::Purchase->value,
            AppEnum::Payment->value, AppEnum::ReceivePayment->value
        ])) {
            $salePurPayReq=$request;
            $salePurPayReq['amount'] = (float) ($salePurPayReq['paid_amount'] ?? 0);
            $ledger = Ledger::create($salePurPayReq);
            $salePurPayReq['ledger_id'] = $ledger->id;
        }
        else
        $ledger = Ledger::create($request);

        $request['ledger_id'] = $ledger->id;

        switch ($request['ledger_type']) {
            case 'sale':
                Sale::create($request);
                if(in_array($request['payment_type'], [AppEnum::Credit->value, AppEnum::Partial->value])) {
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
                $this->stock_service->updateStock($request, $lastQuantity);
                if(in_array($request['payment_type'], [AppEnum::Credit->value, AppEnum::Partial->value]) && $amount>0) {
                    $this->account_payable_service->updateOrInsert($request);
                }
                break;
            case 'receive-payment':
                $this->payment_service->insert($request);
                $this->account_receiveable_service->reduce($request);
                break;
            case 'payment':
                $this->payment_service->insert($request);
                $this->account_payable_service->reduce($request);
                break;
            case 'investment':
            case 'withdraw':
                $newInvestment = self::investmentNewTotal($request);
                Investment::create([
                    'ledger_id' => $ledger->id,
                    'user_id'      => $request['user_id'],
                    'type'         => $request['ledger_type'] ?? 'investment',
                    'category_id'  => $request['category_id'] ?? null,
                    'amount'       => $amount,
                    'total_amount' =>  $newInvestment,
                    'date'         => $request['date'],
                    'payment_method' => $request['payment_method'] ?? null,
                ]);
                break;
        }
        return $ledger;
      });
    }

    public function getDashboardSummary($request): array
    {
        $now = Carbon::now();
        $season_id = $request['season_id'];

        $daily = Ledger::where('ledger_season_id', $season_id)->whereDate('created_at', $now->toDateString())->get();
        $monthly = Ledger::where('ledger_season_id', $season_id)->whereMonth('created_at', $now->month)->get();
        $yearly = Ledger::where('ledger_season_id', $season_id)->whereYear('created_at', $now->year)->get();
        $formatTotals = fn($collection) => [
            'sales' => $collection->where('ledger_type', 'sale')->sum('amount'),
            'expenses' => $collection->where('ledger_type', 'expense')->sum('amount'),
            'purchases' => $collection->where('ledger_type', 'purchase')->sum('amount'),
            'withdraw' => $collection->where('ledger_type', 'withdraw')->sum('amount'),
        ];

        return [
                'daily' => $formatTotals($daily),
                'monthly' => $formatTotals($monthly),
                'yearly' => $formatTotals($yearly),
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
        $ledger= Ledger::where('id', $id)
            ->with(['category','user','investment', 'investment.adjustments', 'sale','sale.adjustments', 'purchase','purchase.adjustments', 'expense', 'expense.adjustments', 'payment', 'payment.adjustments','adjustments'])->first();
        // if($ledger->adjustments){
        //     $ledgerAdjustmentAmount = $ledger->adjustments->sum('amount');
        //     $ledger->amount = $ledger->amount + $ledgerAdjustmentAmount;

        //     // 2. Sale final values
        //     if ($ledger->ledger_type === AppEnum::Sale->value && $ledger->sale) {

        //         $sale = $ledger->sale;

        //         $saleAdjustmentQty = $sale->adjustments->sum('quantity');
        //         $saleAdjustmentAmount = $sale->adjustments->sum('amount');

        //         $sale->quantity = (float) $sale->quantity + $saleAdjustmentQty;
        //         $sale->amount   = (float) $sale->amount + $saleAdjustmentAmount;

        //         // rate remains unchanged
        //         $sale->rate = (float) $sale->rate;
        //     }
        // }
        return $ledger;
    }
    public function buildRequest(int $ledgerId): array
    {
        return $this->ledgerReverseHelper->buildFakeRequest($ledgerId);
    }
    public function update($request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $ledger=self::find($id);
            // | 1. METADATA UPDATE (ALWAYS Allowed to update decription and payment method only)
            LedgerHelper::updateMetadataOnly($ledger, $request);
            // | 2. OWNERSHIP UPDATE (update category and user)
            if (LedgerHelper::hasOwnershipChange($ledger, $request)) {
                $this->ledgerHelper->migrateLedgerOwnership($ledger, $request);
                // return $ledger;
            }
            // | 3. FINANCIAL UPDATE (amount, paid, quantity, rate etc)
            $latestLedger = Ledger::latest()->first();
            $oldEffective = LedgerHelper::getEffectiveAmountFromLedger($ledger);
            $newEffective = $request['paid_amount'] ? $request['paid_amount'] : $request['amount'] ?? 0;
            $delta = LedgerHelper::calculateDelta($ledger, $oldEffective, $newEffective);
            /* ===================== PURCHASE ===================== */
            $purchaseDeltaAmount = 0;
            $rateDiff = 0;
            $qtyDiff  = 0;
            if ($ledger->ledger_type === AppEnum::Purchase->value) {
                $oldQty  = (float) $ledger->purchase->quantity;
                $oldRate = (float) $ledger->purchase->rate;
                $newQty  = $request['quantity'] ?? $oldQty;
                $newRate = $request['rate'] ?? $oldRate;
                $qtyDiff  = $newQty - $oldQty;
                $rateDiff = $newRate - $oldRate;
                $purchaseDeltaAmount = LedgerHelper::quantityRateDelta($oldQty,$oldRate,$newQty,$newRate);
            }
            /* ===================== SALE ===================== */
            $saleDeltaAmount = 0;
            $saleQtyDiff = 0;
            $saleRateDiff = 0;
            $salePaidDelta = 0;
            if ($ledger->ledger_type === AppEnum::Sale->value) {
                $oldQty  = (float) $ledger->sale->quantity;
                $oldRate = (float) $ledger->sale->rate;
                $newQty  = $request['quantity'] ?? $oldQty;
                $newRate = $request['rate'] ?? $oldRate;
                $saleQtyDiff  = $newQty - $oldQty;
                $saleRateDiff = $newRate - $oldRate;
                // TOTAL sale value difference
                $saleDeltaAmount = LedgerHelper::quantityRateDelta($oldQty,$oldRate,$newQty,$newRate);
            }
            $adjustmentAmount = 0;
            $hasPaidAmountChange =
                array_key_exists('paid_amount', $request)
                && ((float) $request['paid_amount'] !== (float) $ledger->amount);

            $hasPurchaseChange = ($purchaseDeltaAmount != 0);
            if ($ledger->ledger_type === AppEnum::Sale->value && $hasPaidAmountChange) {
                $oldReceived = (float) $ledger->amount;
                $newReceived = (float) $request['paid_amount'];
                $salePaidDelta = $newReceived - $oldReceived;
            }
            $adjustmentAmount = LedgerHelper::resolveAdjustmentAmount($ledger, $delta, $hasPaidAmountChange,$salePaidDelta);
            $adjustmentTotal = LedgerHelper::calculateTotalAmount($ledger->ledger_type,$latestLedger->total_amount, $delta);
            if(($delta + $latestLedger->total_amount) < 0){
                throw new \Exception("Not enough blance available. Only {$latestLedger->total_amount } left, & current total is {$delta}");
            }
            
            $paidDelta = 0;
            if ($ledger->ledger_type === AppEnum::Purchase->value && array_key_exists('paid_amount', $request))
            {
                $oldPaid = (float) $ledger->amount;        // previous paid
                $newPaid = (float) $request['paid_amount']; // new paid
                $paidDelta = $newPaid - $oldPaid;
            }

            if ($ledger->ledger_type == $request['ledger_type']) {
                // ====== IMPORTANT FIX (DO NOT CREATE ZERO LEDGER) ======
                if ($delta == 0 && !$hasPurchaseChange && $saleDeltaAmount == 0) {
                    return $ledger;
                }
            $adjustmentLedger = LedgerHelper::createAdjustmentLedger($ledger, $adjustmentAmount,$adjustmentTotal);

            switch ($ledger->ledger_type) {
            case AppEnum::Sale->value:
                $adjustmentLedger->sale()->create([
                    'ledger_id' => $adjustmentLedger->id,
                    'parent_id' => $ledger->sale?->id,
                    'rate'      => $saleRateDiff,
                    'quantity'  => $saleQtyDiff,
                    'amount' => $saleDeltaAmount ?? $delta ?? 0,
                ]);
                if ($saleQtyDiff != 0) {
                    LedgerHelper::adjustStockOnUpdate($ledger, [
                        'quantity' => $saleQtyDiff,
                        'rate'     => $newRate,
                        'category_id' => $ledger->category_id,
                        'ledger_type' => $ledger->ledger_type,
                    ]);
                }
                $receivableDelta = $saleDeltaAmount - $salePaidDelta;
                 if ($receivableDelta != 0) {
                        $this->account_receiveable_service->updateOrInsert([
                            'user_id'     => $ledger->user_id,
                            'category_id' => $ledger->category_id,
                            'ledger_id'   => $adjustmentLedger->id,
                            'amount'      => $receivableDelta,
                        ], true);
                    }
                break;

            case AppEnum::Purchase->value:
                $adjustmentLedger->purchase()->create([
                    'ledger_id' => $adjustmentLedger->id,
                    'parent_id' => $ledger->purchase?->id,
                    'rate'      => $rateDiff ?? $ledger->purchase->rate - $request['rate'],
                    'quantity'  => $qtyDiff,
                    'amount' => $purchaseDeltaAmount ?? $delta,
                ]);
                if ($qtyDiff != 0) {
                    LedgerHelper::adjustStockOnUpdate($ledger, [
                        'quantity' => $qtyDiff,
                        'rate'     => $newRate,
                        'category_id' => $ledger->category_id,
                        'ledger_type' => $ledger->ledger_type,
                    ]);
                }
                if ($ledger->ledger_type === AppEnum::Purchase->value && $hasPurchaseChange) {
                    $purchaseDeltaAmount= $purchaseDeltaAmount ? $purchaseDeltaAmount - $paidDelta : $purchaseDeltaAmount;
                    $this->account_payable_service->updateOrInsert([
                        'user_id'   => $ledger->user_id,
                        'category_id' => $ledger->category_id,
                        'ledger_id' => $adjustmentLedger->id,
                        'amount'    => $purchaseDeltaAmount,
                    ], true);
                }

                break;
            case AppEnum::Expense->value:
                $adjustmentLedger->expense()->create([
                    'ledger_id'     => $adjustmentLedger->id,
                    'parent_id'     => $ledger->expense?->id,
                    'expense_type_id'     => $ledger->expense?->expense_type_id,
                    'amount'        => $adjustmentAmount ?? 0,
                ]);
                break;
            case AppEnum::MoistureLoss->value:
                $moistureLoss=$ledger->expense ?? 0;
                $moistureDeltaQty = ($request['quantity'] ?? 0) - ($moistureLoss->loss_quantity ?? 0);
                $moistureDeltaRate=$request['rate'] - $moistureLoss->rate ?? 0;
                $adjustmentLedger->expense()->create([
                    'ledger_id'     => $adjustmentLedger->id,
                    'parent_id'     => $ledger->expense?->id,
                    'amount'        => $delta*-1 ?? 0,
                    'loss_quantity' => $moistureDeltaQty?? $ledger->expence->quantity,
                    'rate'          => $moistureDeltaRate ?? $ledger->expence->rate,
                ]);
                $lastQuantity = $this->stock_service->checkStock(
                ['category_id'=>$adjustmentLedger->category_id,
                       'ledger_type'=>$adjustmentLedger->ledger_type,
                       'quantity'=>$request['quantity']]
                        );
                if ($lastQuantity < $request['quantity']) {
                    throw new \Exception("Not enough stock available. Only {$lastQuantity} left.");
                }
                $this->stock_service->updateStock([
                    'category_id'=>$ledger->category_id,
                    'ledger_type'=>$ledger->ledger_type,
                    'quantity'=>$moistureDeltaQty,
                ], $lastQuantity);
                break;

            case AppEnum::Payment->value:
            case AppEnum::ReceivePayment->value:
                $adjustmentLedger->payment()->create([
                    'ledger_id' => $adjustmentLedger->id,
                    'parent_id' => $ledger->payment?->id ?? null,
                    'user_id' => $ledger?->user_id,
                    'direction' => $ledger->ledger_type == 'receive-payment'
                                    ? 'receive'
                                    : 'pay',
                    'category_id' => $ledger?->category_id,
                    'paid_amount' =>  $ledger->ledger_type == 'receive-payment' ? $delta : $delta*-1,
                    'amount' => $ledger?->payment?->amount ?? 0,
                    'remaining_amount' => $request['remaining_amount'] ?? 0,
                ]);
                if($ledger->ledger_type==AppEnum::ReceivePayment->value){
                    $this->account_receiveable_service->updateOrInsert([
                            'user_id'     => $ledger->user_id,
                            'category_id' => $ledger->category_id,
                            'ledger_id'   => $adjustmentLedger->id,
                            'amount'      => $delta*-1
                        ], true);
                }else{
                    $this->account_payable_service->updateOrInsert([
                            'user_id'     => $ledger->user_id,
                            'category_id' => $ledger->category_id,
                            'ledger_id'   => $adjustmentLedger->id,
                            'amount'      => $delta
                        ], true);
                }
                break;

            case AppEnum::Investment->value:
            case AppEnum::Withdraw->value:
                $amount = $ledger->ledger_type === AppEnum::Investment->value ? $delta : $adjustmentAmount;
                LedgerHelper::createInvestmentEntry($ledger,$adjustmentLedger,$amount);
                break;
            }
            }
            return $ledger;
        });
    }
    public function billNumber()
    {
        $count = Ledger::count() ?? 0;
        return $count + 1;
    }
    
    public function activeSeason()
    {
        return LedgerSeason::getActiveSeason();
    }

    public function report($request)
    {
        return $this->report_service->generateReport($request);
    }
    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $ledger = LedgerHelper::loadLedgerForDeletion($id);
            LedgerHelper::guardBaseLedger($ledger);
            // 1. Calculate net active investment
            $netEffect = LedgerHelper::calculateNetEffect($ledger);
            if ($netEffect != 0.0 ) {
                $this->createReversalLedger($ledger, $netEffect);
            }
            $this->softDeleteTree($ledger);
            // 3. Soft delete ledger itself
            $ledger->delete();
        });
    }

    private function softDeleteTree(Ledger $ledger): void
    {
        $this->softDeleteLedgerAdjustments($ledger);
        match ($ledger->ledger_type) {
            AppEnum::Investment->value      => LedgerHelper::softDeleteInvestment($ledger),
            AppEnum::Withdraw->value        => LedgerHelper::softDeleteWithdraw($ledger),
            AppEnum::Expense->value         => LedgerHelper::softDeleteExpense($ledger),
            AppEnum::MoistureLoss->value         => LedgerHelper::softDeleteExpense($ledger),
            AppEnum::Purchase->value        => LedgerHelper::softDeletePurchase($ledger),
            AppEnum::Sale->value            => LedgerHelper::softDeleteSale($ledger),
            AppEnum::Payment->value,
            AppEnum::ReceivePayment->value  => LedgerHelper::softDeletePayment($ledger),
            default => null,
        };
    }

    private function softDeleteLedgerAdjustments(Ledger $ledger): void
    {
        $latestAdjustmentId = Ledger::where('parent_id', $ledger->id)
            ->whereNull('deleted_at')
            ->latest('id')
            ->value('id');

        if ($latestAdjustmentId) {
            Ledger::where('parent_id', $ledger->id)
                ->where('id', '<', $latestAdjustmentId)
                ->delete();
        }
    }

    private function createReversalLedger(Ledger $ledger, float $netEffect)
    {
        $amount = -1 * $netEffect;
        $latestTotal = Ledger::whereNull('deleted_at')->latest('id')->value('total_amount') ?? 0;
        $adjustment = Ledger::create([
            'parent_id'    => $ledger->id,
            'ledger_type'  => $ledger->ledger_type,
            'type'         => $ledger->type,
            'user_id'  => $ledger->user_id ?? null,
            'amount'       => $amount,
            'category_id'  => $ledger->category_id,
            'description'  => "Reversal for deleted ledger #{$ledger->id}",
            'total_amount' => LedgerHelper::calculateReversalTotal(
                $ledger->ledger_type,
                $latestTotal,
                in_array($ledger->ledger_type, [AppEnum::Expense->value, AppEnum::Sale->value, AppEnum::MoistureLoss->value, AppEnum::Withdraw->value]) ? $netEffect : $amount
            ),
        ]);

        $this->reverseChildEntities($ledger, $adjustment, $amount);
    }
    public function reverseChildEntities(Ledger $original, Ledger $adjustment, float $amount)
    {
        match ($original->ledger_type) {
            AppEnum::Investment->value,
            AppEnum::Withdraw->value
                => $this->reverseInvestment($original, $adjustment, $amount),

            AppEnum::Expense->value,
            AppEnum::MoistureLoss->value
                => $this->ledgerReverseHelper->reverseExpense(
                    $original,
                    $adjustment,
                    $amount
                ),

            AppEnum::Purchase->value
                => $this->ledgerReverseHelper->reversePurchase(
                    $original,
                    $adjustment
                ),

            AppEnum::Sale->value
                => $this->ledgerReverseHelper->reverseSale(
                    $original,
                    $adjustment
                ),

            AppEnum::Payment->value,
            AppEnum::ReceivePayment->value
                => $this->ledgerReverseHelper->reversePayment(
                    $original,
                    $adjustment
                ),
        };
    }
    private function reverseInvestment(Ledger $original, Ledger $adjustment, float $amount): void
    {
        LedgerHelper::createInvestmentEntry($original, $adjustment, $amount, $original->category_id);
    }

}
