<?php

namespace App\Services\Interfaces;

interface ExpenseTypeServiceInterface extends BaseServiceInterface
{
    public function findall(array $filters);
}
