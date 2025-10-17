<?php

namespace App\Services;

use App\AppEnum;
use App\Models\AccountReceivable;
use App\Models\CreditSale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountReceiveableService
{
    public function updateOrInsert(array $request, bool $isUpdate = false): void
    {
        DB::transaction(function () use ($request, $isUpdate) {
            $record = AccountReceivable::firstOrNew([
                'customer_id' => $request['customer_id'],
                'category_id' => $request['category_id'],
            ]);

            if (! $isUpdate) {
                // Creation time — just add the current amount
                $record->balance = ($record->balance ?? 0) + $request['remaining_amount'];
            } else {
                // Update time — recalc full sum of all related credit sales
                $totalCreditSales = CreditSale::where('customer_id', $request['customer_id'])
                    ->where('category_id', $request['category_id'])
                    ->where('status', AppEnum::UnPaid)
                    ->sum('remaining_amount');

                $record->balance = $totalCreditSales;
            }

            $record->save();
        });
    }

    public function reduce(array $request): void
    {
        $customerId = $request['customer_id'];
        $paidAmount = (float) $request['paid_amount'];
        $categoryId = $request['category_id'];

        DB::transaction(function () use ($customerId, $paidAmount, $categoryId) {
            $remainingPayment = $paidAmount;

            $creditSales = CreditSale::where('customer_id', $customerId)
                ->where('category_id', $categoryId)
                ->where('remaining_amount', '>', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($creditSales as $sale) {
                if ($remainingPayment <= 0) break;

                $available = (float) $sale->remaining_amount; // current owed on this sale

                if ($available <= 0) continue;

                if ($remainingPayment >= $available) {
                    // fully settle this sale
                    $remainingPayment -= $available;
                    $sale->remaining_amount = 0;
                    $sale->status = AppEnum::Paid->value;
                    $sale->save();
                } else {
                    // partial pay
                    $sale->remaining_amount = $available - $remainingPayment;
                    $sale->status = $sale->remaining_amount > 0 ? AppEnum::Partial->value : AppEnum::Paid->value;
                    $sale->save();
                    $remainingPayment = 0;
                    break;
                }
            }

            // update account receivable (ensure record exists)
            $record = AccountReceivable::firstOrNew([
                'customer_id' => $customerId,
                'category_id' => $categoryId,
            ]);
            $record->balance = max(0, ($record->balance ?? 0) - $paidAmount);
            $record->save();
        });
    }

    public function restore(int $customerId, float $amount, int $categoryId): void
    {
        DB::transaction(function () use ($customerId, $amount, $categoryId) {
            $remainingRestore = $amount;

            // Get paid / partial sales in reverse receive-payment order (most recently affected first)
            $paidSales = CreditSale::with('ledger')
                ->where('customer_id', $customerId)
                ->where('category_id', $categoryId)
                ->whereIn('status', [AppEnum::Paid->value, AppEnum::Partial->value])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            foreach ($paidSales as $sale) {
                if ($remainingRestore <= 0) break;

                // ORIGINAL amount for this sale (as you requested we take from ledger relation)
                $original = (float) ($sale->ledger->remaining_amount ?? 0); // original sale total (your model)
                $currentRemaining = (float) $sale->remaining_amount;        // current owed

                // how much was actually paid on this sale (the amount we can restore)
                $paidOnSale = max(0, $original - $currentRemaining);
                if ($paidOnSale <= 0) {
                    // nothing to restore here
                    continue;
                }

                $restoreAmount = min($remainingRestore, $paidOnSale);

                // restore only this portion
                $sale->remaining_amount = $currentRemaining + $restoreAmount;

                // set status correctly:
                if ($sale->remaining_amount <= 0) {
                    $sale->status = AppEnum::Paid->value;
                } elseif ($sale->remaining_amount < $original) {
                    $sale->status = AppEnum::Partial->value;
                } else {
                    $sale->status = AppEnum::UnPaid->value;
                }

                $sale->save();

                $remainingRestore -= $restoreAmount;
            }

            // If still left (rare), try topping up oldest unpaid sales up to their ledger cap
            if ($remainingRestore > 0) {
                $unpaidSales = CreditSale::with('ledger')
                    ->where('customer_id', $customerId)
                    ->where('category_id', $categoryId)
                    ->where('status', AppEnum::UnPaid->value)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($unpaidSales as $sale) {
                    if ($remainingRestore <= 0) break;

                    $original = (float) ($sale->ledger->remaining_amount ?? 0);
                    $currentRemaining = (float) $sale->remaining_amount;
                    $maxAdd = max(0, $original - $currentRemaining);
                    if ($maxAdd <= 0) continue;

                    $add = min($remainingRestore, $maxAdd);
                    $sale->remaining_amount = $currentRemaining + $add;
                    $sale->status = $sale->remaining_amount < $original ? AppEnum::Partial->value : AppEnum::UnPaid->value;
                    $sale->save();

                    $remainingRestore -= $add;
                }
            }

            // If there is still remainingRestore > 0 after all attempts, log (manual reconciliation may be needed)
            if ($remainingRestore > 0) {
                Log::warning("AccountReceiveableService::restore - leftover {$remainingRestore} for customer {$customerId}, category {$categoryId}");
            }

            // Update AccountReceivable: add back the restored total
            $record = AccountReceivable::firstOrNew([
                'customer_id' => $customerId,
                'category_id' => $categoryId,
            ]);
            $record->balance = ($record->balance ?? 0) + $amount;
            $record->save();
        });
    }
}
