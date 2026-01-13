<?php

namespace App\Services;
use App\Models\AccountReceivable;
use Illuminate\Support\Facades\DB;

class AccountReceiveableService
{
    public function updateOrInsert(array $request, bool $isUpdate = false): void
    {
            DB::transaction(function () use ($request, $isUpdate) {
                $record = AccountReceivable::firstOrNew([
                    'user_id' => $request['user_id'],
                    'category_id' => $request['category_id'],
                ]);

                if (! $isUpdate) {
                    $record->balance = ($record->balance ?? 0) + $request['remaining_amount'];
                } else {
                    $record->balance += $request['amount'];
                }

                $record->save();
            });
    }
    public function reduce(array $request): void
    {
        $UserId = $request['user_id'];
        $paidAmount = (float) $request['paid_amount'];
        $categoryId = $request['category_id'];

        DB::transaction(function () use ($UserId, $paidAmount, $categoryId) {
            // update account receivable (ensure record exists)
            $record = AccountReceivable::firstOrNew([
                'user_id' => $UserId,
                'category_id' => $categoryId,
            ]);
            $record->balance = max(0, ($record->balance ?? 0) - $paidAmount);
            $record->save();
        });
    }

    public function checkAccountReceivable($data,$amount=0)  {
        $lastAccountReceivable = AccountReceivable::where('category_id', $data['category_id']
        )->where('user_id', $data['user_id'])->first();

        $lastAccountReceivableBal = $lastAccountReceivable?->balance ?? 0;
        $available = $lastAccountReceivableBal - $amount;
            // Validation: prevent sale if insufficient balance
            if ($available < 0) {
                throw new \Exception("Not enough blance available. Only {$lastAccountReceivableBal} left current total is {$amount}.");
            }
        return $lastAccountReceivableBal;

    }
}
