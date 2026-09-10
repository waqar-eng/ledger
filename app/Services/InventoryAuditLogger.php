<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class InventoryAuditLogger
{
    public function log(string $action, array $data = []): void
    {
        Log::channel('inventory')->info($action, [
            ...$data,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);
    }
}
