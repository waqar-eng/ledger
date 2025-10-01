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
        [$start_date, $end_date] = app(LedgerService::class)->parseDates($filters['start_date'] ?? '', $filters['end_date'] ?? '');

        return $this->buildQuery($filters, $start_date, $end_date);
    }
    private function buildQuery(array $filters, $start_date, $end_date)
    {
        return Stock::with(['category'])
            ->when($start_date && $end_date, fn($q) => app(LedgerService::class)->applyDateFilters($q, $start_date, $end_date))
            
            ->when(!empty($filters['category_id']), fn($q) =>
            $q->whereHas('category', fn($catQ) =>
                $catQ->where('category_id', 'like', '%' . $filters['category_id'] . '%')
                )
            )
            ->orderByDesc('id')->get();
    }

    public function checkStock($data)  {
        $lastStock = Stock::where('category_id', $data['category_id'])->first();

        $lastQuantity = $lastStock?->total_quantity ?? 0;

        if ($data['ledger_type'] === AppEnum::Sale->value) {
            // Validation: prevent sale if insufficient stock
            if ($lastQuantity < $data['quantity']) {
                throw new \Exception("Not enough stock available. Only {$lastQuantity} left.");
            }
        }
        return $lastQuantity;
        
    }

    public function updateStock(array $data, $lastQuantity)
    {
        if ($data['ledger_type'] === AppEnum::Sale->value) {

            $newQuantity = $lastQuantity - $data['quantity'];
        } else {
            // Default: purchase or other types increase stock
            $newQuantity = $lastQuantity + $data['quantity'];
        }
        return Stock::updateOrCreate(
            ['category_id' => $data['category_id']], // condition to match
            [
                'total_quantity' => $newQuantity,
            ]
        );
    }


}
