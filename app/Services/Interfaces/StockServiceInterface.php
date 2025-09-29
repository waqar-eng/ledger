<?php

namespace App\Services\Interfaces;

interface StockServiceInterface extends BaseServiceInterface
{
    // Custom user service methods
    public function findAll($filters);
        
}