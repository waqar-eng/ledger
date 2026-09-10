<?php

namespace App\Logging;

use App\Services\InventoryLogPathService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class InventoryLogger
{
    public function __construct(
        private InventoryLogPathService $inventoryLogPathService
    ) {}
    public function __invoke(array $config)
    {
        $file = $this->inventoryLogPathService->getLogFilePath();

        $logger = new Logger('inventory');

        $logger->pushHandler(
            new StreamHandler(
                $file,
                Logger::INFO
            )
        );

        return $logger;
    }
}
