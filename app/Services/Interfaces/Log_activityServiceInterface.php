<?php

namespace App\Services\Interfaces;

interface Log_activityServiceInterface extends BaseServiceInterface
{
    public function all(array $filters = []);
}
