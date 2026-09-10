<?php

namespace App\Http\Middleware;

use App\Models\Ledger;
use App\Services\InventoryAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    public function __construct(
        private InventoryAuditLogger $inventoryAuditLogger
    ) {}
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $ledger = $request->input('_inventory_ledger');

        if (!$ledger instanceof Ledger) {
            return;
        }

        $this->inventoryAuditLogger->log(
            'Inventory Update ' . $ledger->ledger_type,
            [
                'request' => $request->all(),
                'ledger' => $ledger,
            ]
        );
    }
}
