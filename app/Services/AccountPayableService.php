<?php

namespace App\Services;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use Illuminate\Support\Facades\DB;

class AccountPayableService
{
    public function updateOrInsert(array $request, bool $isUpdate = false): void
    {
        DB::transaction(function () use ($request, $isUpdate) {
            $record = AccountPayable::firstOrNew([
                'user_id' => $request['user_id'],
                'category_id' => $request['category_id'],
                'season_id' => $request['season_id'],
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
        $userId = $request['user_id'];
        $paidAmount = (float) $request['paid_amount'];
        $categoryId = $request['category_id'];

        DB::transaction(function () use ($userId, $paidAmount, $categoryId) {
            // Update AccountPayable balance
            $record = AccountPayable::firstOrNew([
                'user_id' => $userId,
                'category_id' => $categoryId,
            ]);

            $record->balance = max(0, ($record->balance ?? 0) - $paidAmount);
            $record->save();
        });
    }

    public function checkAccountPayable($data,$amount=0)  {
        $lastAccountPayable = AccountPayable::where('category_id', $data['category_id']
        )->where('user_id', $data['user_id'])->first();

        $lastAccountPayableBal = $lastAccountPayable?->balance ?? 0;
        $available = $lastAccountPayableBal - $amount;
            // Validation: prevent sale if insufficient balance
            if ($available < 0) {
                throw new \Exception("Not enough blance available. Only {$lastAccountPayableBal} left, & current total is {$amount}");
            }
        return $lastAccountPayableBal;

    }
    public function getReceivablePayable($season_id)
    {
        // RECEIVABLE
        $receivable = AccountReceivable::with('user')->where('season_id', $season_id)->get();

        // PAYABLE
        $payable = AccountPayable::with('user')->where('season_id', $season_id)->get();

        return [
            'receivable' => [
                'total' => $receivable->sum('balance'),
                'count' => $receivable->count(),
                'data' => $receivable
            ],
            'payable' => [
                'total' => $payable->sum('balance'),
                'count' => $payable->count(),
                'data' => $payable
            ],
        ];
    }
}
