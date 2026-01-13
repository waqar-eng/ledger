<?php

namespace App\Services\Helpers;

use App\AppEnum;
use App\Models\Investment;
use App\Models\Ledger;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Model;

class LedgerHelper
{
    public static function adjustStockOnUpdate($ledger, $request)
    {
        $stockService = app(StockService::class);
        $oldQty = $ledger->quantity ?? 0;
        $currentStock = $stockService->checkStock($request,$oldQty);

        return match ($request['ledger_type']) {
            'sale', 'moisture_loss' => $stockService->updateStock($request, $currentStock + $oldQty),
            'purchase' => $stockService->updateStock($request, $currentStock - $oldQty),
            default => null,
        };
    }
    public static function resolvePaymentType(array $request): string
    {
        $r = (float)($request['remaining_amount'] ?? 0);
        $p = (float)($request['paid_amount'] ?? 0);

        return ($r > 0 && $p > 0)
            ? AppEnum::Partial->value
            : ($r > 0
                ? AppEnum::Credit->value
                : AppEnum::Cash->value);
    }
    public static function loadLedgerForDeletion(int $id)
    {
        return Ledger::with([
            'investment','investment.adjustments',
            'sale','sale.adjustments',
            'purchase','purchase.adjustments',
            'expense','expense.adjustments',
            'payment','payment.adjustments',
            'adjustments'
        ])
        ->whereNull('deleted_at')
        ->findOrFail($id);
    }
    public static function guardBaseLedger(Ledger $ledger): void
    {
        if ($ledger->id === Ledger::min('id')) {
            throw new \Exception('Base investment cannot be deleted.');
        }
    }
    public static function guardStock($availableStock)
    {
        throw new \Exception("Not enough stock available. Only {$availableStock} left.");
    }
    public static function calculateNetEffect(Ledger $ledger)
    {
        // Base amount from main ledger
        $baseAmount = self::getEffectiveAmountFromLedger($ledger);

        // Sum of adjustment children (same ledger_type, parent_id = this ledger)
        $adjustments = Ledger::where('parent_id', $ledger->id)
            ->where('ledger_type', $ledger->ledger_type)
            ->sum('amount');
            // return [$baseAmount, $adjustments];
        $net = (float) ($baseAmount + $adjustments);
        if ($ledger->ledger_type === AppEnum::Investment->value) {

            $withdrawals = Investment::where('user_id', $ledger->user_id)
                ->where('type', AppEnum::Withdraw->value)
                ->where('created_at', '>', $ledger->created_at)
                ->sum('amount');

            $net -= $withdrawals;
        }

        return $net;
    }
    public static function getEffectiveAmountFromLedger($ledger): float
    {
        return (float)  match ($ledger->ledger_type) {

            AppEnum::Sale->value =>
                $ledger?->amount ?? 0,

            AppEnum::Purchase->value =>
                $ledger?->amount ?? 0,

            AppEnum::ReceivePayment->value,
            AppEnum::Payment->value =>
                $ledger->payment?->paid_amount ?? 0,

            AppEnum::Investment->value,
            AppEnum::Withdraw->value =>
                $ledger->investment?->amount ?? 0,

            AppEnum::Expense->value,
            AppEnum::MoistureLoss->value =>
                $ledger->expense?->amount ?? 0,

            default => 0,
        };
    }
    public static function calculateReversalTotal(string $type, float $latest, float $amount): float
    {
        return in_array($type, [
            AppEnum::Purchase->value,
            AppEnum::Sale->value,
            AppEnum::Payment->value
        ])
            ? $latest - $amount
            : $latest + $amount;
    }
    public static function calculateTotalAmount(string $type, float $latest, float $amount): float
    {
        return in_array($type, [
            AppEnum::Sale->value,
        ])
            ? $latest - $amount
            : $latest + $amount;
    }
    public static function adjustedSum(Model $base, string $column)
    {
        return (float) $base->{$column}
            + ($base->adjustments()->whereNull('deleted_at')->sum($column) ?? 0);
    }
    public static function adjustedParentSum(Model $base, string $column)
    {
        return (float) $base->{$column}
            + ($base->adjustments->whereNull('deleted_at')->sum($column) ?? 0);
    }
    public static function adjustBalance(string $model, Ledger $ledger, float $amount)
    {
        $model::where('user_id', $ledger->user_id)
        ->where('category_id', $ledger->category_id)
        ->increment('balance', $amount);
    }

    public static function createReversal(string $model, Ledger $original, Ledger $adjustment, Model $parent, array $payload)
    {
        $model::create(array_merge([
            'user_id'     => $original->user_id,
            'ledger_id'   => $adjustment->id,
            'parent_id'   => $parent->id,
            'category_id' => $original->category_id,
            'date'        => now()->toDateString(),
        ], $payload));
    }
    public static function deleteOlderAdjustments(Model $model): void
    {
        $latestId = $model->adjustments()
            ->latest('id')
            ->value('id');

        if ($latestId) {
            $model->adjustments()
                ->where('id', '<', $latestId)
                ->delete();
        }
    }
    public static function softDeleteInvestment(Ledger $ledger)
    {
        $investment = $ledger->investment;
            if (!$investment) {
                return;
            }
            $latestAdjustmentId = $ledger->adjustments()
            ->latest('id')
            ->value('id');
            $latestAdjustmentInvestmentId = $ledger->investment->adjustments()
            ->latest('id')
            ->value('id');

            if ($latestAdjustmentId) {
                $ledger->adjustments()
                    ->where('id', '<', $latestAdjustmentId)
                    ->delete();
            }
            $withdrawLedgerIds = Ledger::where('user_id', $ledger->user_id)
                ->where('ledger_type', AppEnum::Withdraw->value)
                ->where('created_at', '>', $ledger->created_at)
                ->pluck('id');
            Investment::where('parent_id', $investment->id)
            ->where('id', '<', $latestAdjustmentInvestmentId)->delete();
            Investment::whereIn('ledger_id', $withdrawLedgerIds)->delete();
            Ledger::whereIn('id', $withdrawLedgerIds)->delete();
            // $ledger->adjustments()->delete();
            $investment->delete();
    }
    public static function softDeleteWithdraw(Ledger $ledger): void
    {
        $investment = $ledger->investment;
        if (!$investment) return;

        LedgerHelper::deleteOlderAdjustments($investment);

        $investment->delete();
    }
    public static function softDeleteExpense(Ledger $ledger): void
    {
        $expense = $ledger->expense;
        if (!$expense) return;

        LedgerHelper::deleteOlderAdjustments($expense);

        $expense->delete();
    }
    public static function softDeletePurchase(Ledger $ledger): void
    {
        $purchase = $ledger->purchase;
        if (!$purchase) return;

        LedgerHelper::deleteOlderAdjustments($purchase);

        $purchase->delete();
    }
    public static function softDeleteSale(Ledger $ledger): void
    {
        $sale = $ledger->sale;
        if (!$sale) return;

        LedgerHelper::deleteOlderAdjustments($sale);

        $sale->delete();
    }
    public static function softDeletePayment(Ledger $ledger): void
    {
        $payment = $ledger->payment;
        if (!$payment) return;
        LedgerHelper::deleteOlderAdjustments($payment);
        $payment->delete();
    }
    public static function calculateDelta(Ledger $ledger,float $oldEffective,float $newEffective)
    {
        // Determine how much the change in this ledger affects future running totals.
        // For sales/investments/received-payments the total increases forward in time (new - old).
        // For purchases/expenses/payments the total decreases forward in time (old - new).
        return in_array($ledger->ledger_type, [
            AppEnum::Sale->value,
            AppEnum::Investment->value,
            AppEnum::ReceivePayment->value,
        ])
            ? ($newEffective - $oldEffective)
            : ($oldEffective - $newEffective);
    }
    public static function calculateNewTotal(Ledger $latest, Ledger $ledger, float $delta): float
    {
        return self::calculateReversalTotal(
            $ledger->ledger_type,
            $latest->total_amount,
            $delta
        );
    }
    public static function createAdjustmentLedger(Ledger $ledger, float $amount, float $total)
    {
        return Ledger::create([
            'parent_id'     => $ledger->id,
            'bill_no'       => $ledger->bill_no,
            'ledger_type'   => $ledger->ledger_type,
            'type'          => $ledger->type,
            'amount'        => $amount,
            'total_amount' => $total,
            'season_id'     => $ledger->season_id,
            'description'  => 'Adjustment for Type '.$ledger->ledger_type.' #'.$ledger->id,
            'user_id'       => $ledger->user_id,
            'category_id'   => $ledger->category_id,
            'created_at'    => now(),
        ]);
    }
    public static function resolveAdjustmentAmount(Ledger $ledger,float $delta,bool $hasPaidAmountChange, float $salePaidDelta)
    {
        return match ($ledger->ledger_type) {
            AppEnum::Purchase->value =>
                $hasPaidAmountChange ? $delta*-1 : 0,
            AppEnum::Sale->value =>
                $salePaidDelta,
            AppEnum::Withdraw->value =>
                $delta*-1,
            AppEnum::Expense->value,AppEnum::MoistureLoss->value =>
                $delta*-1,
            AppEnum::Payment->value =>
                $delta*-1,

            default =>
                $delta,
        };
    }
    public static function quantityRateDelta(float $oldQty, float $oldRate, float $newQty, float $newRate)
    {
        return ($newQty * $newRate) - ($oldQty * $oldRate);
    }
    public static function createInvestmentEntry(Ledger $originalLedger,Ledger $adjustmentLedger,float $amount,?int $categoryId = null, ?string $paymentMethod = null): void
    {
        $latestInvestment = Investment::where('user_id', $originalLedger->user_id)->latest('id')->first();
        Investment::create([
            'user_id'        => $originalLedger->user_id,
            'ledger_id'      => $adjustmentLedger->id,
            'parent_id'      => $originalLedger->investment?->id,
            'category_id'    => $categoryId ?? $originalLedger->investment?->category_id,
            'payment_method'=> $paymentMethod ?? $originalLedger->investment?->payment_method,
            'type'           => $originalLedger->ledger_type,
            'amount'         => $amount,
            'total_amount'   => ($latestInvestment->total_amount ?? 0) + ($originalLedger->ledger_type == AppEnum::Withdraw->value? $amount*-1 :$amount),
            'date'           => now()->toDateString(),
        ]);
    }


}
