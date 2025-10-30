<?php

namespace App\Repositories\Interfaces;

interface LedgerSeasonRepositoryInterface extends BaseRepositoryInterface
{
    public function search(array $filter);

}
