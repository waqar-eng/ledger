<?php

namespace App\Services\Helpers;

use App\AppEnum;
use App\Models\Investment;
use App\Models\Ledger;
use App\Services\AccountPayableService;
use App\Services\AccountReceiveableService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\ReportService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class LedgerHelper
{
    public function __construct(
    private StockService $stock_service,
    private AccountReceiveableService $account_receiveable_service,
    private PaymentService $payment_service,
    private AccountPayableService $account_payable_service,
    private ReportService $report_service,
    private PurchaseService $purchase_service,
    )
    {
    }
    public static function adjustStockOnUpdate($ledger, $request)
    {
        $stockService = app(StockService::class);
        $oldQty = $ledger->quantity ?? 0;
        $currentStock = $stockService->checkStock($request,$oldQty);
        if (in_array($request['ledger_type'], ['sale', 'moisture_loss'])) {
            return $stockService->updateStock($request, $currentStock + $oldQty);}
        if ($request['ledger_type'] === 'purchase') {
            if (($currentStock + $request['quantity'])<0) {
                throw new \Exception("Not enough stock available. Only {$currentStock} left.");
            }
            return $stockService->updateStock($request, $currentStock - $oldQty);
        };
        return null;
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
        return  $latest + $amount;
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
    public static function updateMetadataOnly(Ledger $ledger, array $request): void
    {
        $ledger->update([
            'description'    => $request['description']    ?? $ledger->description,
            'payment_method' => $request['payment_method'] ?? $ledger->payment_method,
        ]);
    }
    public static function hasOwnershipChange(Ledger $ledger, array $request): bool
    {
        return
            (isset($request['user_id']) && $request['user_id'] != $ledger->user_id)
            || (isset($request['category_id']) && $request['category_id'] != $ledger->category_id);
    }
    public function migrateLedgerOwnership(Ledger $ledger, array $request): void
    {
        if ($ledger->adjustments()->exists()) {
            throw new \Exception("Adjusted ledgers are not allowed to update their ownership.");
        }
        $oldUser     = $ledger->user_id;
        $oldCategory = $ledger->category_id;

        $newUser     = $request['user_id']     ?? $oldUser;
        $newCategory = $request['category_id'] ?? $oldCategory;
        $requestAmount = $request['amount'] ?? 0;
        if ($oldCategory !== $newCategory) {
            throw new \Exception("Category change is not allowed.");
        }

        switch ($ledger->ledger_type) {

            case AppEnum::Sale->value:
                $this->migrateSaleOwnership($ledger, $oldUser, $newUser, $oldCategory, $newCategory);
                break;

            case AppEnum::Purchase->value:
                $this->migratePurchaseOwnership($ledger, $oldUser, $newUser, $oldCategory, $newCategory);
                break;
            case AppEnum::Withdraw->value:
                $this->migrateWithdrawOwnership($ledger, $oldUser, $newUser, $oldCategory, $newCategory, $requestAmount);
                break;
            case AppEnum::Investment->value:
                $this->migrateInvestmentOwnership($ledger, $oldUser, $newUser, $oldCategory, $newCategory);
                break;
            case AppEnum::Payment->value:
                $this->migratePaymentOwnership($ledger, $oldUser, $newUser, $oldCategory, $newCategory);
                break;
            case AppEnum::ReceivePayment->value:
                $this->migrateReceivePaymentOwnership($ledger, $oldUser, $newUser, $oldCategory, $newCategory);
                break;
        }
        // Update base ledger AFTER reversals
        $ledger->update([
            'user_id'     => $newUser,
        ]);
    }
    public function migrateSaleOwnership(Ledger $ledger,int $oldUser,int $newUser,int $oldCategory, int $newCategory): void
    {
        $sale = $ledger->sale;

        $total = (float) $sale->quantity * $sale->rate;
        $paid  = (float) $ledger->amount;
        $remaining = $total - $paid;
        /* | 1. RECEIVABLE: REMOVE FROM OLD USER */
        if ($remaining > 0) {
            $this->account_receiveable_service->checkAccountReceivable([
                'category_id'=>$oldCategory,
                'user_id'=>$oldUser,
        ], $remaining);
            $this->account_receiveable_service->updateOrInsert([
                'user_id'     => $oldUser,
                'category_id' => $oldCategory,
                'amount'      => -$remaining,
            ], true);
        }

        /* | 2. RECEIVABLE: ADD TO NEW USER  */
        if ($remaining > 0) {
            $this->account_receiveable_service->updateOrInsert([
                'user_id'     => $newUser,
                'category_id' => $oldCategory,
                'amount'      => $remaining,
            ], true);
        }
    }
    public function migratePurchaseOwnership(Ledger $ledger,int $oldUser,int $newUser,int $oldCategory,int $newCategory): void
    {
        $purchase = $ledger->purchase;
        $total = $purchase->quantity * $purchase->rate;
        $paid  = $ledger->amount;
        $remaining = $total - $paid;
        if ($remaining > 0) {
            $this->account_payable_service->checkAccountPayable([
                    'category_id'=>$oldCategory,
                    'user_id'=>$oldUser,
            ], $remaining);
            $this->account_payable_service->updateOrInsert([
                'user_id'     => $oldUser,
                'category_id' => $oldCategory,
                'amount'      => -$remaining,
            ], true);

            $this->account_payable_service->updateOrInsert([
                'user_id'     => $newUser,
                'category_id' => $oldCategory,
                'amount'      => $remaining,
            ], true);
        }
    }
    public function migrateWithdrawOwnership(Ledger $ledger,int $oldUser,int $newUser,int $oldCategory,int $newCategory, $requestAmount): void
    {
        if($oldUser==$newUser){
            return;
        }
        $newUserLatestInvestment = Investment::where('user_id', $newUser)
            ->latest('id')
            ->first();
        $availableBalance = $newUserLatestInvestment->total_amount ?? 0;
        if ($availableBalance < $requestAmount) {
            throw new \Exception('Insufficient balance to transfer ownership.');
        }
        $ledger->investment->update([
            'user_id'=>$newUser,
            'category_id'=>$oldCategory,
            'total_amount' => $availableBalance - $requestAmount,
        ]);
    }
    public function migrateInvestmentOwnership(Ledger $ledger,int $oldUser,int $newUser,int $oldCategory,int $newCategory): void
    {
        if($oldUser==$newUser){
            return;
        }
        $investment = $ledger->investment;
        $hasWithdraw = Investment::where('user_id', $investment->user_id)
        ->where('type', 'withdraw')
        ->where('id', '>', $investment->id)
        ->exists();
        if ($hasWithdraw) {
            throw new \DomainException(
                'Investment ownership cannot be changed after withdrawal.'
            );
        }
        $investment->update([
            'user_id'     => $newUser,
            'category_id' => $oldCategory,
        ]);
    }
    public function migratePaymentOwnership(Ledger $ledger,int $oldUser,int $newUser,int $oldCategory,int $newCategory): void
    {
        if($oldUser==$newUser){
            return;
        }
        $payment = $ledger->payment;
        $this->account_payable_service->checkAccountPayable([
                'category_id'=>$oldCategory,
                'user_id'=>$oldUser,
        ], $payment->paid_amount);
        $this->account_payable_service->updateOrInsert([
                'user_id'     => $oldUser,
                'category_id' => $oldCategory,
                'amount'      => -$payment->paid_amount,
            ], true);
        $this->account_payable_service->checkAccountPayable([
                'category_id'=>$newCategory,
                'user_id'=>$newUser,
        ], $payment->paid_amount);
        $this->account_payable_service->updateOrInsert([
                'user_id'     => $newUser,
                'category_id' => $newCategory,
                'amount'      => $payment->paid_amount*-1,
            ], true);
        $payment->update(['user_id'=>$newUser, 'category_id'=>$newCategory]);
    }
    public function migrateReceivePaymentOwnership(Ledger $ledger,int $oldUser,int $newUser,int $oldCategory,int $newCategory): void
    {
        if($oldUser==$newUser){
            return;
        }
        $payment = $ledger->payment;
        $this->account_receiveable_service->checkAccountReceivable([
                'category_id'=>$oldCategory,
                'user_id'=>$oldUser,
        ], $payment->paid_amount);
        $this->account_receiveable_service->updateOrInsert([
                'user_id'     => $oldUser,
                'category_id' => $oldCategory,
                'amount'      => $payment->paid_amount,
            ], true);
        $this->account_receiveable_service->checkAccountReceivable([
                'category_id'=>$newCategory,
                'user_id'=>$newUser,
        ], $payment->paid_amount);
        $this->account_receiveable_service->updateOrInsert([
                'user_id'     => $newUser,
                'category_id' => $newCategory,
                'amount'      => $payment->paid_amount*-1,
            ], true);
        $payment->update(['user_id'=>$newUser, 'category_id'=>$newCategory]);
    }






}
