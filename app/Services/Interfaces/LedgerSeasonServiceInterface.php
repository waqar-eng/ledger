<?php

namespace App\Services\Interfaces;

interface LedgerSeasonServiceInterface extends BaseServiceInterface
{
    public function search(array $filter);
    public function updateSeason(array $data, $id);

}
