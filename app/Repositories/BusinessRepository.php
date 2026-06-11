<?php

namespace App\Repositories;

use App\Models\Business;
use App\Repositories\Interfaces\BusinessRepositoryInterface;

class BusinessRepository extends BaseRepository implements BusinessRepositoryInterface
{
    public function __construct(Business $model)
    {
        parent::__construct($model);
    }
}
