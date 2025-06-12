<?php

namespace App\Services;

use App\Repositories\Interfaces\PurchaseRepositoryInterface;
use App\Services\Interfaces\PurchaseServiceInterface;
use App\Models\Purchase;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Ledger;

class PurchaseService extends BaseService implements PurchaseServiceInterface
{
    protected $purchaseRepositoryInterface;

    public function __construct(PurchaseRepositoryInterface $purchaseRepositoryInterface)
    {
        parent::__construct($purchaseRepositoryInterface);
        $this->purchaseRepositoryInterface = $purchaseRepositoryInterface;
    }

    public function all(array $filters = [])
    {


        $query = Purchase::with(['ledger', 'ledger.customer']);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {

            $start = Carbon::parse($filters['start_date'])->toDateString();
            $end = Carbon::parse($filters['end_date'])->toDateString();

            $query->whereHas('ledger', function ($q) use ($start, $end) {
                Log::info("Inside whereHas for ledger.date filtering");
                $q->whereDate('date', '>=', $start)
                    ->whereDate('date', '<=', $end);
            });
        }

        $results = $query->get();
        return $results;
    }

}
