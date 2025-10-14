<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\Interfaces\CategoryServiceInterface;
use Exception;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryServiceInterface $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    public function index(Request $request)
    {
        try {
            $categories = $this->categoryService->findAll($request->all());
            return $this->success($categories ,Category::CATEGORYS_FETCHED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }


    public function store(CategoryRequest $request)
    {
        try {
            $category = $this->categoryService->create($request->all());

            return $this->success($category, Category::CATEGORY_CREATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(CategoryRequest $request)
    {
        try {
            $category = $this->categoryService->find($request->id);
            return $this->success($category ,Category::CATEGORY_FETCHED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(CategoryRequest $request )
    {
        try {

            $category = $this->categoryService->update($request->array(), $request->id);

            return $this->success($category, Category::CATEGORY_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(CategoryRequest $request)
    {
        try {
            $this->categoryService->delete($request->id);
            return $this->success(null, Category::CATEGORY_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
