<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LedgerRequest;
use App\Models\Ledger;
use App\Services\Interfaces\LedgerServiceInterface;
use Exception;
use App\Services\LedgerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    use AuthorizesRequests;
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
            return $this->success($user);
            return $this->error(Ledger::UPDATE_RESTRICTED, 500);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(LedgerRequest $request, $id)
    {
        try {
            $ledger = $this->ledgerService->update($request->all(), $id);

            return $ledger
                ? $this->success($ledger, Ledger::LEDGER_UPDATED)
                : $this->error(Ledger::LEDGER_TYPE_RESTRICTED, 500);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(LedgerRequest $request)
    {
        try {
            // $ledger = Ledger::findOrFail($request->id);
            // $this->authorize('delete', $ledger);
            $res=$this->ledgerService->delete($request->id);
            if($res)
            return $this->success($res, Ledger::LEDGER_DELETED);
            return $this->error($res);
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
