<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\Interfaces\CustomerServiceInterface;
use Exception;

class CustomerController extends Controller
{

    protected $customerService;

    public function __construct(CustomerServiceInterface $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(CustomerRequest $request)
    {
        try {
            $customers = $this->customerService->findAll($request->all());
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

    public function update(CustomerRequest $request)
    { 
        try {
            $this->authorizeModelAction('update', Customer::class, $request['id']);
            $customer = $this->customerService->update($request->array() , $request->id);
            return $this->success($customer, Customer::CUSTOMER_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->authorizeModelAction('delete', Customer::class, $id);
            $this->customerService->delete($id);
            return $this->success(null, Customer::CUSTOMER_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
