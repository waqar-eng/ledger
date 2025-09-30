<?php

namespace App\Services;

use App\AppEnum;
use App\Constants\AppConstants;
use App\Models\Stock;
use App\Repositories\Interfaces\StockRepositoryInterface;
use App\Services\Interfaces\StockServiceInterface;

class StockService extends BaseService implements StockServiceInterface
{
 public function __construct(StockRepositoryInterface $StockRepository)
    {
        parent::__construct($StockRepository);
    }
    public function findAll($filters)
   {
        $perPage = $filters['per_page'] ?? AppConstants::DEFAULT_PER_PAGE;
        [$start_date, $end_date] = app(LedgerService::class)->parseDates($filters['start_date'] ?? '', $filters['end_date'] ?? '');

        return $this->buildQuery($filters, $start_date, $end_date, $perPage);
    }
    private function buildQuery(array $filters, $start_date, $end_date, $perPage)
    {
        return Stock::with(['ledger', 'ledger.category'])
            ->when($start_date && $end_date, fn($q) => app(LedgerService::class)->applyDateFilters($q, $start_date, $end_date))
             
            ->when(!empty($filters['ledger_type']), fn($q) =>
            $q->whereHas('ledger', fn($catQ) =>
                $catQ->where('ledger_type', 'like', '%' . $filters['ledger_type'] . '%')
                )
            )
            
            ->when(!empty($filters['category_id']), fn($q) =>
            $q->whereHas('ledger.category', fn($catQ) =>
                $catQ->where('category_id', 'like', '%' . $filters['category_id'] . '%')
                )
            )
            ->orderByDesc('id')->paginate($perPage);
    }

    public function checkStock($data)  {
        $lastStock = Stock::where('category_id', $data['category_id'])
            ->latest('id')
            ->first();

        $lastQuantity = $lastStock?->total_quantity ?? 0;

        if ($data['ledger_type'] === AppEnum::Sale->value) {
            // Validation: prevent sale if insufficient stock
            if ($lastQuantity < $data['quantity']) {
                throw new \Exception("Not enough stock available. Only {$lastQuantity} left.");
            }
            return $lastQuantity;
        }
        
    }

    public function updateStock(array $data, $lastQuantity)
    {
        if ($data['ledger_type'] === AppEnum::Sale->value) {

            $newQuantity = $lastQuantity - $data['quantity'];
        } else {
            // Default: purchase or other types increase stock
            $newQuantity = $lastQuantity + $data['quantity'];
        }

        return Stock::create([
            'ledger_id'      => $data['ledger_id'],
            'category_id'    => $data['category_id'],
            'total_quantity' => $newQuantity,
        ]);
    }


}
