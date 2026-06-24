<?php

namespace App\Services;

use App\AppEnum;
use App\Repositories\Interfaces\LedgerRepositoryInterface;
use App\Services\Interfaces\LedgerServiceInterface;
use App\Models\Ledger;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\LedgerSeason;
use App\Services\Helpers\LedgerHelper;
use App\Services\Helpers\LedgerReverseHelper;
use App\Services\Ledger\CalculationService;
use App\Services\Ledger\QueryService;
use Illuminate\Support\Facades\DB;

class LedgerService extends BaseService implements LedgerServiceInterface
{
    public function __construct(
    LedgerRepositoryInterface $repository,
    private StockService $stock_service,
    private QueryService $query_service,
    private CalculationService $calculation_service,
    private AccountReceiveableService $account_receiveable_service,
    private PaymentService $payment_service,
    private AccountPayableService $account_payable_service,
    private ReportService $report_service,
    private PurchaseService $purchase_service,
    private LedgerHelper $ledgerHelper,
    private LedgerReverseHelper $ledgerReverseHelper,
    private LedgerAccountService $ledgerAccountService,
    )
    {
        parent::__construct($repository);
    }
    public function findAll(array $filters)
    {
        return $this->query_service->findAll($filters);
    }

    public function create($request)
    {
        return DB::transaction(function () use ($request) {
        $this->ledgerAccountService->validateAccountBalances(
            $request['ledger_type'],
            $request['accounts'] ?? []
        );
        //Step 1: Get the previous total amount from last valid ledger
        $amount = $request['amount'] ?? 0;
        $lastQuantity = $this->stock_service->checkStock($request );
        $typeAndNewtotal = $this->calculation_service->ledgerNewTotalAndType($request);
        $request['payment_type'] = LedgerHelper::resolvePaymentType($request);
        // Set derived fields in request data
        $request['type'] = $typeAndNewtotal['type'];
        $request['total_amount'] = $typeAndNewtotal['newTotal'];
        $data = $request;

        if (in_array(
            $request['ledger_type'],
            [
                AppEnum::Sale->value,
                AppEnum::Purchase->value,
                AppEnum::Payment->value,
                AppEnum::ReceivePayment->value,
            ]
        )) {
            $data['amount'] = (float) ($request['paid_amount'] ?? 0);
        }

        $ledger = Ledger::create($data);

        $request['ledger_id'] = $ledger->id;
        $this->ledgerAccountService->createLedgerAccounts(
            $ledger->id,
            $request['accounts'] ?? []
        );
        $this->calculation_service->updateAccountBalances($request);

        $this->handleLedgerType(
            $request,
            $amount,
            $lastQuantity
        );
        return $ledger;
      });
    }
    private function handleLedgerType(
        array $request,
        float $amount,
        float $lastQuantity
    ): void {
        switch ($request['ledger_type']) {

            case AppEnum::Sale->value:
                Sale::create($request);

                if (in_array(
                    $request['payment_type'],
                    [AppEnum::Credit->value, AppEnum::Partial->value]
                )) {
                    $this->account_receiveable_service->updateOrInsert($request);
                }

                $this->stock_service->updateStock($request, $lastQuantity);
                break;

            case AppEnum::Expense->value:
                Expense::create($request);
                break;

            case AppEnum::MoistureLoss->value:
                $request['loss_quantity'] = $request['quantity'];

                Expense::create($request);
                $this->stock_service->updateStock($request, $lastQuantity);
                break;

            case AppEnum::Purchase->value:
                $this->purchase_service->createWithMoisture($request);
                $this->stock_service->updateStock($request, $lastQuantity);

                if (
                    in_array(
                        $request['payment_type'],
                        [AppEnum::Credit->value, AppEnum::Partial->value]
                    ) &&
                    $amount > 0
                ) {
                    $this->account_payable_service->updateOrInsert($request);
                }
                break;

            case AppEnum::ReceivePayment->value:
                $this->payment_service->insert($request);
                $this->account_receiveable_service->reduce($request);
                break;

            case AppEnum::Payment->value:
                $this->payment_service->insert($request);
                $this->account_payable_service->reduce($request);
                break;

            case AppEnum::Investment->value:
            case AppEnum::Withdraw->value:
                $newTotal = $this->calculation_service->investmentNewTotal($request);
                $request['type'] = $request['ledger_type'] ?? AppEnum::Investment->value;
                Investment::create($request);
                break;
        }
    }

    public function find($id)
    {
        $ledger= Ledger::where('id', $id)
            ->with(['category','user','investment', 'investment.adjustments', 'sale','sale.adjustments', 'purchase','purchase.adjustments', 'expense', 'expense.adjustments', 'payment', 'payment.adjustments','adjustments','accounts'])->first();

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
            $deletion = $request['deletion'] ?? false;
            $deltaledgerAccount = $this->ledgerAccountService->deltaLedgerAccounts($deletion,$id, $request['accounts']);
            $this->ledgerAccountService->validateAccountBalancesAdjustment(
                $deletion,
                $request['ledger_type'],
                $deltaledgerAccount ?? []
            );
            $adjustmentLedger = LedgerHelper::createAdjustmentLedger($ledger, $adjustmentAmount,$adjustmentTotal,$request);

            $request['accounts'] = $deltaledgerAccount;
            $this->ledgerAccountService->createLedgerAccounts(
                $adjustmentLedger->id,
                $deltaledgerAccount ?? []
            );
            $this->calculation_service->updateAccountBalances($request);
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
                            'season_id'   => $ledger->ledger_season_id,
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
                        'season_id'   => $ledger->ledger_season_id,
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
                            'season_id'   => $ledger->ledger_season_id,
                            'user_id'     => $ledger->user_id,
                            'category_id' => $ledger->category_id,
                            'ledger_id'   => $adjustmentLedger->id,
                            'amount'      => $delta*-1
                        ], true);
                }else{
                    $this->account_payable_service->updateOrInsert([
                            'season_id'   => $ledger->ledger_season_id,
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
    public function getDashboardSummary($request)
    {
        return $this->query_service->getDashboardSummary($request);
    }
    public function getReceivablePayable(int $season_id)
    {
        return $this->account_payable_service->getReceivablePayable($season_id);
    }

}
