<?php

namespace App\Services\Interfaces;

interface CustomerServiceInterface extends BaseServiceInterface
{
        public function update(array $request, $id);
         public function findAll(array $filters);
}
