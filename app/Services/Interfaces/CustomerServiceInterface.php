<?php

namespace App\Services\Interfaces;

interface CustomerServiceInterface extends BaseServiceInterface
{
    public function findAll(array $filters);
}
