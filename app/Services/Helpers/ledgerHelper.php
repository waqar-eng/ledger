<?php

namespace App\Services\Helpers;

use App\AppEnum;
use App\Services\StockService;

class LedgerHelper
{
    public static function requiresCustomer(string $ledgerType): bool
    {
        return ! in_array($ledgerType, [
            AppEnum::Investment->value,
            AppEnum::Withdraw->value,
            AppEnum::MoistureLoss->value,
        ], true);
    }
    public static function adjustStockOnUpdate($ledger, $request)
    {
        $stockService = app(StockService::class);
        $oldQty = $ledger->quantity ?? 0;
        $currentStock = $stockService->checkStock($request);

        return match ($request['ledger_type']) {
            'sale', 'moisture_loss' => $stockService->updateStock($request, $currentStock + $oldQty),
            'purchase' => $stockService->updateStock($request, $currentStock - $oldQty),
            default => null,
        };
    }
    public static function resolvePaymentType(array $request): string
    {
        $r = (float)($request['remaining_amount'] ?? 0);
        $p = (float)($request['paid_amount'] ?? 0);

        return ($r > 0 && $p > 0)
            ? AppEnum::Partial->value
            : ($r > 0
                ? AppEnum::Credit->value
                : AppEnum::Cash->value);
    }
}
