<?php

namespace App\Services\Interfaces;

interface LedgerServiceInterface extends BaseServiceInterface
{
    // Custom user service methods
    public function findAll(array $filters);
    
    public function billNumber();

    public function report($request);
    
}
