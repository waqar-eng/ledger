<?php

namespace App\Services;

use Carbon\Carbon;

class InventoryLogPathService
{
    public function getLogFilePath(?Carbon $date = null): string
    {
        $date ??= now();

        $monthYear = $date->format('F-Y');

        $directory = rtrim(
            env('BACKUP_PATH_LOG'),
            '/\\'
        ) . DIRECTORY_SEPARATOR . $monthYear;

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory
            . DIRECTORY_SEPARATOR
            . 'inventory-' . $date->format('Y-m-d') . '.log';
    }
}
