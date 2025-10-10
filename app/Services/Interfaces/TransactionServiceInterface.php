<?php

namespace App\Services\Interfaces;

interface TransactionServiceInterface extends BaseServiceInterface
{
    public function findAll(array $filters);
}
