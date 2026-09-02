<?php

namespace App\Services\Interfaces;

interface AccountServiceInterface extends BaseServiceInterface
{
    public function showAccountTransactions(int $id);
}