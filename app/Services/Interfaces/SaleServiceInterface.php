<?php

namespace App\Services\Interfaces;

interface SaleServiceInterface extends BaseServiceInterface
{
    // Custom user service methods
    public function all(array $filters = []);

    
}