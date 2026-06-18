<?php

namespace App\Services;

use App\AppEnum;
use App\Models\Account;
use App\Models\Ledger;
use App\Models\LedgerAccounts;

class LedgerAccountService
{
    public function createLedgerAccounts(int $ledgerId, array $accounts = []): void
    {
        if (empty($accounts)) {
            return;
        }

        $splits = collect($accounts)
            ->map(fn ($account) => [
                'ledger_id'  => $ledgerId,
                'account_id' => $account['account_id'],
                'amount'     => (float) ($account['amount'] ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->toArray();

        LedgerAccounts::insert($splits);
    }
    public function validateAccountBalances(
        string $ledgerType,
        array $accounts = []
    ): void {

        // Only debit transactions should be checked
        if (!in_array($ledgerType, [
            AppEnum::Expense->value,
            AppEnum::Purchase->value,
            AppEnum::Withdraw->value,
        ])) {
            return;
        }

        foreach ($accounts as $item) {

            $accountId = $item['account_id'] ?? null;
            $amount    = (float) ($item['amount'] ?? 0);

            if (!$accountId || $amount <= 0) {
                continue;
            }

            $account = Account::find($accountId);

            if (!$account) {
                throw new \Exception(
                    "Account {$accountId} not found."
                );
            }

            $availableBalance = (float) $account->opening_balance;

            if ($amount > $availableBalance) {
                throw new \Exception(
                    "Insufficient balance in {$account->name}. ".
                    "Available: {$availableBalance}, ".
                    "Required: {$amount}"
                );
            }
        }
    }
    public function validateAccountBalancesAdjustment(
        bool $deletion,
        string $ledgerType,
        array $accounts = []
    ): void {

        // Only credit transactions should be checked
        if (in_array($ledgerType, [
            AppEnum::Expense->value,
            AppEnum::Purchase->value,
            AppEnum::Withdraw->value,
        ] )&& $deletion) {
            return;
        }

        foreach ($accounts as $item) {

            $accountId = $item['account_id'] ?? null;
            $amount    = (float) ($item['amount'] ?? 0);


            $account = Account::find($accountId);
            $availableBalance = (float) $account->opening_balance;
            if (in_array($ledgerType, [
                AppEnum::Expense->value,
                AppEnum::Purchase->value,
                AppEnum::Withdraw->value,
                AppEnum::Payment->value,
            ] ) && !$deletion)
            {
                if($amount > 0 && ($availableBalance - $amount < 0))
                {
                    throw new \Exception(
                        "Insufficient balance in {$account->name}. ".
                        "Available: {$availableBalance}, ".
                        "Required: {$amount}"   
                    );
                }
            }elseif( in_array($ledgerType, [
                AppEnum::Sale->value,
                AppEnum::Investment->value,
                AppEnum::ReceivePayment->value,
            ] ) )
            {
                if($amount < 0 && ($amount + $availableBalance < 0))
                {
                    throw new \Exception(
                        "Insufficient balance in {$account->name}. ".
                        "Available: {$availableBalance}, ".
                        "Required: {$amount}"   
                    );
                }
            }
        }
    }
    public function deltaLedgerAccounts(bool $deletion, int $ledgerId, array $new_accounts = [])
    {
        $existingAccounts = LedgerAccounts::where('ledger_id', $ledgerId)
            ->get()
            ->keyBy('account_id');

        // Delete case: reverse full existing amounts
        if ($deletion) {
            return $existingAccounts->map(function ($account) {
                return [
                    'account_id' => $account->account_id,
                    'amount'     => (float) $account->amount * -1 ,
                ];
            })->values()->toArray();
        }

        $newAccounts = collect($new_accounts)->keyBy('account_id');

        $deltas = [];

        foreach ($newAccounts as $accountId => $new) {

            $oldAmount = isset($existingAccounts[$accountId])
                ? (float) $existingAccounts[$accountId]->amount
                : 0;

            $newAmount = (float) $new['amount'];

            $delta = $newAmount - $oldAmount;

            if ($delta != 0) {
                $deltas[] = [
                    'account_id' => $accountId,
                    'amount'     => $delta,
                ];
            }
        }

        return $deltas;
    }
}