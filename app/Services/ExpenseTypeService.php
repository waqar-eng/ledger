<?php

namespace App\Services;

use App\Models\ExpenseType;
use App\Repositories\Interfaces\ExpenseTypeRepositoryInterface;
use App\Services\Interfaces\ExpenseTypeServiceInterface;

class ExpenseTypeService extends BaseService implements ExpenseTypeServiceInterface
{
    public function __construct(ExpenseTypeRepositoryInterface $ExpenseTypeRepository)
    {
        parent::__construct($ExpenseTypeRepository);
    }
    public function findall(array $filters){
        return ExpenseType::query()
            ->when(!empty($filters['season_id']), function ($query) use ($filters) {
                $query->where('season_id', $filters['season_id']);
            })
            ->whereNotNull('season_id')
            ->get();
    }
}
