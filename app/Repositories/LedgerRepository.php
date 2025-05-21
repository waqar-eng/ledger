<?php

namespace App\Repositories;

use App\Models\Ledger;
use App\Repositories\Interfaces\LedgerRepositoryInterface;

class LedgerRepository extends BaseRepository implements LedgerRepositoryInterface
{
    public function __construct(Ledger $model)
    {
        parent::__construct($model);
    }
}
