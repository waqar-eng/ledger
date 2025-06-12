<?php

namespace App\Services\Interfaces;

interface ExpenseServiceInterface extends BaseServiceInterface
{
    // Custom user service methods
    public function all(array $filters = []);
    
}