<?php

namespace App\Services;

use App\Models\Account;
use App\Models\LedgerAccounts;
use App\Repositories\Interfaces\AccountRepositoryInterface;
use App\Services\Interfaces\AccountServiceInterface;

class AccountService extends BaseService implements AccountServiceInterface
{
    protected $accountRepositoryInterface;
    /**
     * Create a new class instance.
     */
    public function __construct(AccountRepositoryInterface $accountRepositoryInterface)
    {
        parent::__construct($accountRepositoryInterface);
        $this->accountRepositoryInterface = $accountRepositoryInterface;
    }

    public function all(array $filters = [])
    {
        $query = Account::with('business');
        $results = $query->orderBy('id', 'desc')->get();
        return $results;
    }

    public function showAccountTransactions(int $season_id, int $id)
    {
        $data['account'] = Account::findOrFail($id);
        $data['transactions'] = LedgerAccounts::with([
            'ledger',
            'ledger.season.business',
            'ledger.user'
        ])
            ->where('account_id', $id)
            ->whereRelation('ledger', 'ledger_season_id', $season_id)
            ->orderBy('id', 'DESC')
            ->get();


        return $data;
    }

}
