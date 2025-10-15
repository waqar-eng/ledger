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

}
