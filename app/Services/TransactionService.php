<?php

namespace App\Services;

use App\Jobs\RecalculateTotalsJob;
use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Services\Interfaces\TransactionServiceInterface;
use Illuminate\Support\Facades\DB;

class TransactionService extends BaseService implements TransactionServiceInterface
{
    public function __construct(TransactionRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

public function findAll(array $filters)
{
    $perPage = $filters['per_page'] ?? 10;
    $page = request('page', 1);

    // Step 1: Filtered query
    $query = Transaction::with(['customer', 'category'])
        ->orderBy('id', 'desc');

    if (!empty($filters['customer_id'])) {
        $query->where('customer_id', $filters['customer_id']);
    }

    if (!empty($filters['category_id'])) {
        $query->where('category_id', $filters['category_id']);
    }

    if (!empty($filters['search_term'])) {
        $query->where('description', 'like', '%' . $filters['search_term'] . '%');
    }
    return $this->getFilteredTransactionsWithBalance($query, $page, $perPage);
}

private function getFilteredTransactionsWithBalance($query, $page, $perPage)
{
    $transactions = $query->get();
    $sortedForBalance  = $transactions->sortBy('id')->values();

    $runningBalance = 0;
    foreach ($sortedForBalance as $tran) {
        $amount = (float) $tran->amount;
        $runningBalance += ($tran->type === 'credit') ? $amount : -$amount;

        $original = $transactions->firstWhere('id', $tran->id);
        if ($original) {
            $original->calculated_balance = $runningBalance;
        }
    }

    $paginated = $transactions->forPage($page,$perPage)->values();

    return new \Illuminate\Pagination\LengthAwarePaginator(
        $paginated,
        $transactions->count(),
        $perPage,
        $page,
        ['path' => request()->url(), 'query' => request()->query()]
    );
}
    public function create($request)
    {
          return DB::transaction(function () use ($request) {

            $typeAndNewtotal = self::transactionNewTotalAndType($request);
            $request['balance'] = $typeAndNewtotal['newTotal'];
            $transaction = Transaction::create($request);

            return $transaction;
         });
    }

     public static function transactionNewTotalAndType($request, $id = null)
    {
        $query = Transaction::query();
        if ($id) {
            $query->where('id', '<', $id);
        }

        $latesttransaction = $query->latest()->first();
        $previousTotal = $latesttransaction?->balance ?? 0;
        $type = $request['type'];

        $newTotal = $type === 'credit'
            ? $previousTotal + $request['amount']
            : $previousTotal - $request['amount'];

        return ['newTotal' => $newTotal, 'type' => $type];
    }


public function update($request, $id){
    $transaction = Transaction::findOrFail($id);
    $transaction->update($request);

    RecalculateTotalsJob::dispatch($transaction->id);

    return $transaction;
}

public function delete($id)
{
    $transaction = Transaction::findOrFail($id);
    $transaction->delete();

    RecalculateTotalsJob::dispatch($id);

    return $transaction;
}

}
