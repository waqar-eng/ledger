<?php

namespace App\Services;

use App\Models\payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
public function insert(array $request): void
{
    DB::transaction(function () use ($request) {
        if (in_array($request['ledger_type'], ['receive-payment', 'payment'])) {

            payment::create([
                'ledger_id'   => $request['ledger_id'] ?? null,
                'user_id' => $request['user_id'] ?? null,
                'category_id' => $request['category_id'] ?? null,
                'amount'      => $request['amount'] ?? 0,
                'paid_amount'      => $request['paid_amount'] ?? 0,
                'remaining_amount'      => $request['remaining_amount'] ?? 0,
                'direction'   => $request['ledger_type'] === 'receive-payment' ? 'receive' : 'pay',
            ]);
        }
    });
}

}
