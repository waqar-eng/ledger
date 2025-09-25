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
    public function createWithMoisture(array $data)
    {
        $moisture = $data['moisture'] ?? 0;
        $quantity = $data['quantity']??0;
        $predictedQuantity = $quantity * (1 - $moisture / 100);

        $purchaseData = [
            'ledger_id'          => $data['ledger_id'],
            'moisture'           => $moisture,
            'rate'               => $data['rate'],
            'amount'             => $data['amount'] ?? 0,
            'actual_quantity'    => $quantity,
            'predicted_quantity' => $predictedQuantity,
        ];

        return Purchase::create($purchaseData);
    }

    public function updateWithMoisture($ledgerId, array $data)
    {
        $purchase = Purchase::where('ledger_id', $ledgerId)->firstOrFail();
        $actualQuantity   = $data['quantity'];
        $rate             = $data['rate'];
        $moisture         = $data['moisture'] ?? 0;
        
        $predictedQuantity = $moisture > 0
        ? $actualQuantity - ($actualQuantity * ($moisture / 100))
        : $actualQuantity;
        
        // return [$purchase, $ledgerId, $data, $predictedQuantity];
        $purchase->update([
            'moisture'           => $moisture,
            'rate'               => $rate,
            'amount'             => $data['amount'] ?? 0,
            'actual_quantity'    => $actualQuantity,
            'predicted_quantity' => $predictedQuantity,
        ]);

        return $purchase;
    }


}
