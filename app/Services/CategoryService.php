<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Services\Interfaces\CategoryServiceInterface;

class CategoryService extends BaseService implements CategoryServiceInterface
{
    public function __construct(CategoryRepositoryInterface $CategoryRepository)
    {
        parent::__construct($CategoryRepository);
    }
    public function findall(array $filters){
        return Category::with('stock')
        ->when(!empty($filters['season_id']), function ($query) use ($filters) {
            $query->where('season_id', $filters['season_id']);
        })
        ->get();
    }
}
