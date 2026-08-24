<?php

namespace App\Services\Ledger;

use App\AppEnum;
use App\Models\Account;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Investment;
use App\Models\Ledger;
use App\Models\LedgerSeason;
use Illuminate\Validation\ValidationException;

class CalculationService
{
    public function calculateTotals($data, $filters)
    {
        // Helper flags
        $isCreditPurchase = !empty($filters['is_credit_purchase']);
        $isCreditSale     = !empty($filters['is_credit_sale']);
        $hasUser      = !empty($filters['user_id']);
        $hasCategory      = !empty($filters['category_id']);

        $totals = [
            'sale'       => $data->where('ledger_type', AppEnum::Sale)->sum('amount') ?? 0,
            'purchase'   => $data->where('ledger_type', AppEnum::Purchase)->sum('amount') ?? 0,
            'expense'    => $data->where('ledger_type', AppEnum::Expense)->sum('amount') ?? 0,
            'investment' => $data->where('ledger_type', AppEnum::Investment)->sum('amount') ?? 0,
            'withdrawal' => $data->where('ledger_type', AppEnum::Withdraw)->sum('amount') ?? 0,
            'payment'    => $isCreditPurchase ? $data->where('ledger_type', AppEnum::Payment)->sum('paid_amount') : 0,
            'receive_payment' => $isCreditSale ? $data->where('ledger_type', AppEnum::ReceivePayment)->sum('paid_amount') : 0,
        ];

        $total_amount = optional($data->first())->total_amount ?? 0;
        $total_paid     = $totals['payment'] ?? 0;
        $total_received = $totals['receive_payment'] ?? 0;

        // 1️⃣ User specific total
        if (!empty($filters['user_id'])) {
            $total_amount = $totals['investment'] - $totals['withdrawal'];
        }

        // 2️⃣ Specific ledger type totals
        elseif (!empty($filters['ledger_type'])) {
            $type = $filters['ledger_type'];
            if (in_array($type, ['withdraw', 'investment']) && isset($totals[$type])) {
                $total_amount = $totals[$type];
            }
        }

        // 3️⃣ Category & User totals (Accounts Payable/Receivable)
        elseif ($hasCategory) {
            $query = $isCreditPurchase ? AccountPayable::query() : AccountReceivable::query();
            $query->where('category_id', $filters['category_id']);

            if ($hasUser) {
                $query->where('user_id', $filters['user_id']);
            }

            $total_amount = $query->sum('balance');
            if($isCreditPurchase)
            $total_paid = $data->sum('paid_amount');
            if($isCreditSale)
            $total_received = $data->sum('paid_amount');
        }

        // 4️⃣ User totals only
        elseif ($hasUser) {
            $query = $isCreditPurchase ? AccountPayable::query() : AccountReceivable::query();
            $total_amount = $query->where('user_id', $filters['user_id'])->sum('balance');
            if($isCreditPurchase)
            $total_paid = $data->sum('paid_amount');
            if($isCreditSale)
            $total_received = $data->sum('paid_amount');
        }

        // 5️⃣ All Payable or Receivable totals
        elseif ($isCreditPurchase) {
            $total_amount = AccountPayable::sum('balance');
            $total_paid = $data->sum('paid_amount');
        } elseif ($isCreditSale) {
            $total_amount = AccountReceivable::sum('balance');
            $total_received = $data->sum('paid_amount');
        }

        return array_merge($totals, [
            'total_amount' => $total_amount,
            'total_received' => $total_received,
            'total_paid' => $total_paid,
        ]);
    }

    public static function ledgerNewTotalAndType($request, $id = null)
    {
        $season_id= $request['ledger_season_id'];
         if (!$season_id) {
            throw new \Exception(LedgerSeason::NO_ACTIVE_SEASON);
        }
        $query = Ledger::where('ledger_season_id', $season_id);
        if ($id) {
            $query->where('id', '<', $id);
        }

        $latestLedger = $query->latest()->first();
        $previousTotal  = (float) ($latestLedger?->total_amount ?? 0);
        $ledgerType = $request['ledger_type'];
        $type = self::getLedgerType($ledgerType);

        $ledgerAmount = match ($ledgerType) {
            AppEnum::Sale->value,
            AppEnum::Purchase->value,
            AppEnum::Payment->value,
            AppEnum::ReceivePayment->value
                => (float) ($request['paid_amount'] ?? 0),

            default
                => (float) ($request['amount'] ?? 0),
        };
        $newTotal = $type === AppEnum::Credit->value
        ? $previousTotal + $ledgerAmount
        : $previousTotal - $ledgerAmount;

        if ($newTotal < 0) {
            throw ValidationException::withMessages([
                'amount' => [Ledger::LOW_BALANCE_ERROR],
            ]);
        }
        return ['newTotal' => $newTotal, 'type' => $type];
    }
    public static function getLedgerType($ledger_type)
    {
        return match ($ledger_type) {
            'purchase', 'expense', 'withdraw', 'moisture_loss', 'other','payment' => AppEnum::Debit->value,
            'sale', 'investment','receive-payment' => AppEnum::Credit->value,

            default => throw ValidationException::withMessages([
                'ledger_type' => ['Invalid ledger type.']
            ])
        };
    }

    public static function investmentNewTotal($request, $id = null)
    {
        $query = Investment::where('user_id', $request['user_id']);


        // Exclude current record when updating
        if ($id) {
            $query->where('id', '<', $id);
        }

        $latestInvestment = $query->latest()->first();
        $previousInvestment = $latestInvestment?->total_amount ?? 0;

        // Calculate new total based on amount provided
        $newInvestment = match ($request['ledger_type']) {
            'investment' => $previousInvestment + $request['amount'],
            'withdraw'   => $previousInvestment - $request['amount'],
            default      => $previousInvestment
        };

        if ($newInvestment < 0) {
            throw ValidationException::withMessages([
                'amount' => [Ledger::LOW_BALANCE_ERROR],
            ]);
        }
        return $newInvestment;
    }
    public function updateAccountBalances(array $request): void
    {
        if (empty($request['accounts']) || !is_array($request['accounts'])) {
            return;
        }

        $multiplier = match ($request['ledger_type']) {
            AppEnum::Sale->value,
            AppEnum::Investment->value,
            AppEnum::ReceivePayment->value => 1,

            AppEnum::Purchase->value,
            AppEnum::Expense->value,
            AppEnum::Payment->value,
            AppEnum::Withdraw->value => -1,

            default => 0,
        };

        if ($multiplier === 0) {
            return;
        }

        foreach ($request['accounts'] as $accountData) {
            Account::where('id', $accountData['account_id'])
                ->increment(
                    'opening_balance',
                    (float) ($accountData['amount'] ?? 0) * $multiplier
                );
        }
    }
    public function updateInterAccountTransferBalances(array $request): void
    {
        if (
            empty($request['from_account_id']) ||
            empty($request['to_account_id']) ||
            empty($request['amount'])
        ) {
            return;
        }

        $amount = (float) $request['amount'];

        // Deduct from source account
        Account::where('id', $request['from_account_id'])
            ->decrement('opening_balance', $amount);

        // Add to destination account
        Account::where('id', $request['to_account_id'])
            ->increment('opening_balance', $amount);
    }
}
