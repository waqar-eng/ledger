<?php

namespace App\Services\Helpers;

use App\AppEnum;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Expense;
use App\Models\Ledger;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\AccountPayableService;
use App\Services\AccountReceiveableService;
use App\Services\StockService;
use Faker\Provider\Payment;

class LedgerReverseHelper
{
    public function __construct(
    private AccountReceiveableService $account_receiveable_service,
    private StockService $stock_service,
    private AccountPayableService $account_payable_service,
    )
    {
    }
    public function reverseExpense(Ledger $original, Ledger $adjustment, float $amount)
    {
        $expense = $original->expense;
        if (!$expense) return;

        $payload = ['amount' => $amount];
        $payload = ['expense_type_id' => $expense->expense_type_id];
        // Moisture loss specific fields
        if ($original->ledger_type === AppEnum::MoistureLoss->value) {
            $payload['loss_quantity'] = LedgerHelper::adjustedParentSum(
                $expense,
                'loss_quantity'
            );
            $payload['rate'] = LedgerHelper::adjustedParentSum(
                $expense,
                'rate'
            );
            $availableStock = $this->stock_service->checkStock([
            'category_id' => $original->category_id,
            'ledger_type' => $original->ledger_type,
            'quantity'    => $payload['loss_quantity'],
        ]);
        $this->stock_service->updateStockByDelta($original->category_id, $payload['loss_quantity']);
        }

        Ledgerhelper::createReversal(
            Expense::class,
            $original,
            $adjustment,
            $expense,
            $payload
        );
    }

    public function reversePurchase(Ledger $original, Ledger $adjustment): void
    {
        $basePurchase = $original->purchase;
        if (!$basePurchase) return;
        $adjAmount   = LedgerHelper::adjustedSum($basePurchase, 'amount');
        $adjQuantity = LedgerHelper::adjustedSum($basePurchase, 'quantity');
        $adjRate = LedgerHelper::adjustedSum($basePurchase, 'rate');
        $adjRemain = LedgerHelper::adjustedParentSum($original, 'amount');
        $availableStock = $this->stock_service->checkStock([
            'category_id' => $original->category_id,
            'ledger_type' => $original->ledger_type,
            'quantity'    => $adjQuantity,
        ]);
        if ($availableStock < $adjQuantity) {
            LedgerHelper::guardStock($availableStock);
        }
        $this->account_payable_service->checkAccountPayable(
            ['category_id' => $original->category_id, 'user_id' => $original->user_id],
            $adjRemain
        );
        LedgerHelper::createReversal(Purchase::class, $original,$adjustment,$basePurchase,
        ['quantity' => -$adjQuantity, 'amount' => -$adjAmount, 'rate' => -$adjRate]);
        $this->stock_service->updateStockByDelta($original->category_id, -$adjQuantity);
        LedgerHelper::adjustBalance(AccountPayable::class, $original, -$adjRemain);
    }
    public function reverseSale(Ledger $original, Ledger $adjustment)
    {
        $baseSale = $original->sale;
        if (!$baseSale) {return;}

        $adjAmount   = LedgerHelper::adjustedSum($baseSale, 'amount');
        $adjQuantity = LedgerHelper::adjustedSum($baseSale, 'quantity');
        $adjRemain = LedgerHelper::adjustedParentSum($original, 'amount');

        $this->account_receiveable_service->checkAccountReceivable(
            ['category_id' => $original->category_id, 'user_id' => $original->user_id],
            $adjRemain
        );
        $this->stock_service->updateStockByDelta($original->category_id, $adjQuantity);
        LedgerHelper::adjustBalance(AccountReceivable::class, $original, -$adjRemain);

        LedgerHelper::createReversal(Sale::class, $original, $adjustment, $baseSale,
        ['quantity' => -$adjQuantity, 'amount' => -$adjAmount]
        );
    }
    public function reversePayment(Ledger $original, Ledger $adjustment): void {
        $basePayment = $original->payment;
        if (!$basePayment) {return;}

        $adjPaidAmt   = LedgerHelper::adjustedSum($basePayment, 'paid_amount');

        LedgerHelper::createReversal(Payment::class,$original,$adjustment, $basePayment,
        [
                'paid_amount'      => -$adjPaidAmt,
                'remaining_amount'=> $basePayment->remaining_amount ?? 0,
                'amount'           => $basePayment->amount ?? 0,
                'direction'        => $original->ledger_type === AppEnum::ReceivePayment->value
                                        ? 'receive'
                                        : 'pay',
            ]
        );
        $model = $original->ledger_type === AppEnum::ReceivePayment->value
        ? AccountReceivable::class : AccountPayable::class;
        LedgerHelper::adjustBalance($model, $original, $adjPaidAmt);
    }
    public function buildFakeRequest(int $ledgerId): array
    {
        $ledger = Ledger::with([
            'sale',
            'purchase',
            'expense',
            'investment',
        ])->findOrFail($ledgerId);
        $rate = 0;
        switch ($ledger->ledger_type) {
            case 'sale':
                if ($ledger->sale) {
                    $rate     = $ledger->sale->rate;
                }
                break;

            case 'purchase':
                if ($ledger->purchase) {
                    $rate     = $ledger->purchase->rate;
                }
                break;
        }
        return [
            'parent_id'        => $ledger->id,
            'ledger_type'      => $ledger->ledger_type,
            'user_id'          => $ledger->user_id,
            'category_id'      => $ledger->category_id,
            'ledger_season_id' => $ledger->ledger_season_id,

            'amount'           => 0,
            'paid_amount'      => 0,
            'quantity'         => 0,
            'rate'             => $rate,
            'bill_no'          => $ledger->bill_no,
        ];
    }

}
