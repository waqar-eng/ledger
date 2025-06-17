<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\Interfaces\CustomerServiceInterface;
use App\Traits\ApiResponseTrait;
use Exception;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    protected $customerService;

    public function __construct(CustomerServiceInterface $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index()
    {
        try {
            $customers = $this->customerService->all();
            return $this->success($customers);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(CustomerRequest $request)
    {
        try {
            $customer = $this->customerService->create($request->validated());
            return $this->success($customer, Customer::CUSTOMER_CREATED, 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $customer = $this->customerService->find($id);
            return $this->success($customer);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(CustomerRequest $request, $id)
    {
        try {
            $customer = $this->customerService->update($request->validated(), $id);
            return $this->success($customer, Customer::CUSTOMER_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->customerService->delete($id);
            return $this->success(null, Customer::CUSTOMER_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
