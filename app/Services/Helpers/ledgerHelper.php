<?php

namespace App\Services\Helpers;

use App\AppEnum;

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
}
