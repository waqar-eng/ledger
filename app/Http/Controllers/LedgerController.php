<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LedgerRequest;
use App\Services\Interfaces\LedgerServiceInterface;
use App\Traits\ApiResponseTrait;
use Exception;

class LedgerController extends Controller
{
    use ApiResponseTrait;

    protected $ledgerService;

    public function __construct(LedgerServiceInterface $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function index()
    {
        try {
            $ledger = $this->ledgerService->all();
            // $ledger = $this->ledgerService->with('customer')->get();

            return $this->success($ledger);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(LedgerRequest $request)
    {
        try {
           $user = $this->ledgerService->create($request->validated());
            return $this->success($user, 'ledger created successfully', 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $user = $this->ledgerService->find($id);
            return $this->success($user);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(LedgerRequest $request, $id)
    {
        try {
            $user = $this->ledgerService->update($request->validated(), $id);
            return $this->success($user, 'ledger updated successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->ledgerService->delete($id);
            return $this->success(null, 'ledger deleted successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
