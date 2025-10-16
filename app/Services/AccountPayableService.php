<?php

namespace App\Services;

use App\AppEnum;
use App\Models\AccountPayable;
use App\Models\CreditPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountPayableService
{
    public function updateOrInsert(array $request, bool $isUpdate = false): void
    {
        DB::transaction(function () use ($request, $isUpdate) {
            $record = AccountPayable::firstOrNew([
                'customer_id' => $request['customer_id'],
                'category_id' => $request['category_id'],
            ]);

            if (! $isUpdate) {
                $record->balance = ($record->balance ?? 0) + $request['remaining_amount'];
            } else {
                $totalUnpaid = CreditPurchase::where('customer_id', $request['customer_id'])
                    ->where('category_id', $request['category_id'])
                    ->where('status', AppEnum::UnPaid)
                    ->sum('remaining_amount');

                $record->balance = $totalUnpaid;
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

            $creditPurchases = CreditPurchase::where('customer_id', $customerId)
                ->where('category_id', $categoryId)
                ->where('remaining_amount', '>', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($creditPurchases as $purchase) {
                if ($remainingPayment <= 0) break;

                $available = (float) $purchase->remaining_amount;

                if ($remainingPayment >= $available) {
                    $remainingPayment -= $available;
                    $purchase->update([
                        'remaining_amount' => 0,
                        'status' => AppEnum::Paid->value,
                    ]);
                } else {
                    $purchase->update([
                        'remaining_amount' => $available - $remainingPayment,
                        'status' => AppEnum::Partial->value,
                    ]);
                    $remainingPayment = 0;
                    break;
                }
            }

            // Update AccountPayable balance
            $record = AccountPayable::firstOrNew([
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

            $paidPurchases = CreditPurchase::with('ledger')
                ->where('customer_id', $customerId)
                ->where('category_id', $categoryId)
                ->whereIn('status', [AppEnum::Paid->value, AppEnum::Partial->value])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            foreach ($paidPurchases as $purchase) {
                if ($remainingRestore <= 0) break;

                $original = (float) ($purchase->ledger->remaining_amount ?? 0);
                $current = (float) $purchase->remaining_amount;
                $paidPart = max(0, $original - $current);

                if ($paidPart <= 0) continue;

                $restoreAmount = min($remainingRestore, $paidPart);
                $purchase->remaining_amount = $current + $restoreAmount;

                if ($purchase->remaining_amount < $original) {
                    $purchase->status = AppEnum::Partial->value;
                } elseif ($purchase->remaining_amount >= $original) {
                    $purchase->status = AppEnum::UnPaid->value;
                } else {
                    $purchase->status = AppEnum::Paid->value;
                }

                $purchase->save();
                $remainingRestore -= $restoreAmount;
            }

            // Update AccountPayable balance
            $record = AccountPayable::firstOrNew([
                'customer_id' => $customerId,
                'category_id' => $categoryId,
            ]);

            $record->balance = ($record->balance ?? 0) + $amount;
            $record->save();

            if ($remainingRestore > 0) {
                Log::warning("AccountPayableService::restore - leftover {$remainingRestore} for customer {$customerId}, category {$categoryId}");
            }
        });
    }
}
