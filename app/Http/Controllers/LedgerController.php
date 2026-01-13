<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LedgerRequest;
use App\Models\Ledger;
use App\Services\Interfaces\LedgerServiceInterface;
use Exception;
use App\Services\LedgerService;
use Illuminate\Http\Request;

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
            $user = $this->ledgerService->find($id);
            return $user ? $this->success($user) : 
            $this->error(Ledger::UPDATE_RESTRICTED, 500);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(LedgerRequest $request, $id)
    {
        try {
            $this->authorizeModelAction('update', Ledger::class, $id);
            $ledger = $this->ledgerService->update($request->all(), $id);
            $this->success($ledger, Ledger::LEDGER_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(LedgerRequest $request)
    {
        try {
            $this->authorizeModelAction('delete', Ledger::class, $request->id);
            $res=$this->ledgerService->delete($request->id);
            return $this->success($res, Ledger::LEDGER_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    public function billNumber()
    {
        try {
            $billNumber=$this->ledgerService->billNumber();
            return $this->success($billNumber, Ledger::BILL_NUMBER_SUCCESS);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    public function report(Request $request)
    {
        try {
            $report=$this->ledgerService->report($request->all());
            return $this->success($report, Ledger::REPORT_SUCCESS);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
