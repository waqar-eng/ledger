<?php

namespace App\Repositories;


use App\Models\Sale;
use App\Repositories\Interfaces\SaleRepositoryInterface;

class SaleRepository extends BaseRepository implements SaleRepositoryInterface
{
    public function __construct(Sale $model)
    {
        parent::__construct($model);
    }
}
