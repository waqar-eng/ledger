<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ledger\LedgerRequest;
use App\Services\Interfaces\LedgerServiceInterface;
use App\Traits\ApiResponseTrait;

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
        $ledger = $this->ledgerService->all();
        return $this->success($ledger);
    }

    public function store(LedgerRequest $request)
    {
        $user = $this->ledgerService->create($request->validated());
        return $this->success($user, 'User created successfully', 201);
    }

    public function show($id)
    {
        $user = $this->ledgerService->find($id);
        return $this->success($user);
    }

    public function update(LedgerRequest $request, $id)
    {
        $user = $this->ledgerService->update($request->validated(), $id);
        return $this->success($user, 'User updated successfully');
    }

    public function destroy($id)
    {
        $this->ledgerService->delete($id);
        return $this->success(null, 'User deleted successfully');
    }
}
