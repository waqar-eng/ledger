<?php

namespace App\Repositories;

use App\Models\ExpenseType;
use App\Repositories\Interfaces\ExpenseTypeRepositoryInterface;

class ExpenseTypeRepository extends BaseRepository implements ExpenseTypeRepositoryInterface
{
       public function __construct(ExpenseType $model)
    {
        parent::__construct($model);
    }
}
