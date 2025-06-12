<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\ExpenseServiceInterface;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\ExpenseRequest;
use Illuminate\Http\Request;
use Exception;

class ExpenseController extends Controller
{
    use ApiResponseTrait;

    protected $expenseService;

    public function __construct(ExpenseServiceInterface $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['start_date', 'end_date']);
            $expenses = $this->expenseService->all($filters);
            return $this->success($expenses);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(ExpenseRequest $request)
    {
        try {
            $expense = $this->expenseService->create($request->all());
            return $this->success($expense, 'Expense created successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $expense = $this->expenseService->find($id);
            return $this->success($expense);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(ExpenseRequest $request, $id)
    {
        try {
            $expense = $this->expenseService->update($request->all(), $id);
            return $this->success($expense, 'Expense updated successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->expenseService->delete($id);
            return $this->success(null, 'Expense deleted successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
