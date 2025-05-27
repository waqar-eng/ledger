<?php

namespace App\Repositories;


use App\Models\Purchase;
use App\Repositories\Interfaces\PurchaseRepositoryInterface;

class PurchaseRepository extends BaseRepository implements PurchaseRepositoryInterface
{
    public function __construct(Purchase $model)
    {
        parent::__construct($model);
    }
}
