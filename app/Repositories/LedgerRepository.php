<?php

namespace App\Repositories;

use App\Models\LedgerEntry;

class LedgerRepository extends BaseRepository implements LedgerRepositoryInterface
{
    public function __construct(LedgerEntry $model)
    {
        parent::__construct($model);
    }
}
