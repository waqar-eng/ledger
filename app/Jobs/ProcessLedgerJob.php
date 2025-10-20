<?php

namespace App\Jobs;

use App\Services\LedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLedgerJob implements ShouldQueue
{
     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $action;   // 'create' | 'update' | 'delete'
    protected ?int $ledgerId;
    protected array $request;

    /**
     * @param string $action  'create', 'update', 'delete'
     * @param int|null $ledgerId
     * @param array $request
     */
    public function __construct(string $action, ?int $ledgerId = null, array $request = [])
    {
        $this->action = $action;
        $this->ledgerId = $ledgerId;
        $this->request = $request;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $ledgerService = app(LedgerService::class);

            match ($this->action) {
                'create' => $ledgerService->handleCreate($this->request),
                'update' => $ledgerService->handleUpdate($this->request, $this->ledgerId),
                'delete' => $ledgerService->handleDelete($this->ledgerId),
                default  => throw new \InvalidArgumentException("Invalid ledger action: {$this->action}")
            };

        } catch (\Throwable $e) {
            Log::error("ProcessLedgerJob failed [{$this->action}]: " . $e->getMessage(), [
                'ledgerId' => $this->ledgerId,
                'request' => $this->request,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // optional: rethrow for retry
        }
    }
}
