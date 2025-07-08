<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LedgerRequest;
use App\Models\Ledger;
use App\Services\Interfaces\LedgerServiceInterface;
use Exception;
use App\Services\LedgerService;


class LedgerController extends Controller
{
     
    protected LedgerService $ledgerService;

    public function __construct(LedgerServiceInterface $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function dashboardSummary()
    {
        $summary = $this->ledgerService->getDashboardSummary();

        return response()->json([
            'status' => true,
            'message' => 'Dashboard summary fetched successfully',
            'data' => $summary
        ]);
    }

    public function index(LedgerRequest $request)
    {
        try {
            $ledger = $this->ledgerService->findAll($request->all());
            return $this->success($ledger);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(LedgerRequest $request)
    {
        try {
           $user = $this->ledgerService->create($request->validated());
            return $this->success($user, Ledger::LEDGER_CREATED, 201);
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
            return $this->success($user, Ledger::LEDGER_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->ledgerService->delete($id);
            return $this->success(null, Ledger::LEDGER_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
