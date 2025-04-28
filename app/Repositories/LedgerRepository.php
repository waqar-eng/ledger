<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\LedgerRepositoryInterface;

class LedgerRepository extends BaseRepository implements LedgerRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
