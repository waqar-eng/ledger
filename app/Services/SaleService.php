<?php

namespace App\Services;


use App\Repositories\Interfaces\SaleRepositoryInterface;
use App\Services\Interfaces\SaleServiceInterface;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;
use App\Models\Ledger;



class SaleService extends BaseService implements SaleServiceInterface
{
    protected $saleRepositoryInterface;
    /**
     * Create a new class instance.
     */
    public function __construct(SaleRepositoryInterface $saleRepositoryInterface)
    {
        parent::__construct($saleRepositoryInterface);
        $this->saleRepositoryInterface = $saleRepositoryInterface;
    }

    public function all(array $filters = [])
    {


        $query = Sale::with(['ledger', 'ledger.customer']);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $start = \Carbon\Carbon::parse($filters['start_date'])->toDateString();
            $end = \Carbon\Carbon::parse($filters['end_date'])->toDateString();

            $query->whereHas('ledger', function ($q) use ($start, $end) {
                $q->whereDate('date', '>=', $start)
                    ->whereDate('date', '<=', $end);
            });
        }

        $results = $query->get();


        return $results;
    }

  
}
