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
        try {
             $summary = $this->ledgerService->getDashboardSummary();
             return $this->success($summary);
        } catch (Exception $e){
            return $this->error($e->getMessage(),500);
        }
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
           $user = $this->ledgerService->create($request->all());
            return $this->success($user, Ledger::LEDGER_CREATED, 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            if($this->ledgerService->isLatestLedger($id)){
            $user = $this->ledgerService->find($id);
            return $this->success($user);
            }
            return $this->error(Ledger::UPDATE_RESTRICTED, 500);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(LedgerRequest $request, $id)
    {
        try {
            if($this->ledgerService->isLatestLedger($id)){
                $user = $this->ledgerService->update($request->all(), $id);
                return $this->success($user, Ledger::LEDGER_UPDATED);
            }
            return $this->error(Ledger::UPDATE_RESTRICTED, 500);
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
