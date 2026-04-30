<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseTypeRequest;
use App\Models\ExpenseType;
use App\Services\Interfaces\ExpenseTypeServiceInterface;
use Exception;
use Illuminate\Http\Request;

class ExpenseTypeController extends Controller
{
    protected $expenseTypeService;

    public function __construct(ExpenseTypeServiceInterface $expenseTypeService)
    {
        $this->expenseTypeService = $expenseTypeService;
    }

    public function index(ExpenseTypeRequest $request)
    {
        try {
            $expenseTypes = $this->expenseTypeService->findAll($request->all());

            return $this->success($expenseTypes, ExpenseType::EXPENSE_TYPES_FETCHED);

        } catch (Exception $e) {

            return $this->error($e->getMessage(), 500);

        }
    }

    public function store(ExpenseTypeRequest $request)
    {
        try {

            $expenseType = $this->expenseTypeService->create($request->all());

            return $this->success($expenseType, ExpenseType::EXPENSE_TYPE_CREATED);

        } catch (Exception $e) {

            return $this->error($e->getMessage(), 500);

        }
    }

    public function show(ExpenseTypeRequest $request)
    {
        try {

            $expenseType = $this->expenseTypeService->find($request->id);

            return $this->success($expenseType, ExpenseType::EXPENSE_TYPE_FETCHED);

        } catch (Exception $e) {

            return $this->error($e->getMessage(), 500);

        }
    }

    public function update(ExpenseTypeRequest $request)
    {
        try {

            $this->authorizeModelAction('update', ExpenseType::class, $request['id']);

            $expenseType = $this->expenseTypeService->update($request->all(), $request->id);

            return $this->success($expenseType, ExpenseType::EXPENSE_TYPE_UPDATED);

        } catch (Exception $e) {

            return $this->error($e->getMessage(), 500);

        }
    }

    public function destroy(ExpenseTypeRequest $request)
    {
        try {

            $this->authorizeModelAction('delete', ExpenseType::class, $request['id']);

            $this->expenseTypeService->delete($request->id);

            return $this->success(null, ExpenseType::EXPENSE_TYPE_DELETED);

        } catch (Exception $e) {

            return $this->error($e->getMessage(), 500);

        }
    }
}