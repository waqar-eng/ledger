<?php

namespace App\Services\Interfaces;

use App\Services\Interfaces\BaseServiceInterface;

interface CategoryServiceInterface extends BaseServiceInterface
{
        public function findAll(array $filters);

}
