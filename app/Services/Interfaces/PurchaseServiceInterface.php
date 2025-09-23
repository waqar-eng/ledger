<?php

namespace App\Services\Interfaces;

interface PurchaseServiceInterface extends BaseServiceInterface
{
    // Custom user service methods
    public function all(array $filters = []);

}
