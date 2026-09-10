<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\InterAccountTransferRequest;
use App\Http\Requests\LedgerRequest;
use App\Http\Requests\ReceivablePayableRequest;
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

    public function dashboardSummary(LedgerRequest $request)
    {
        try {
             $summary = $this->ledgerService->getDashboardSummary($request);
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
           $ledger = $this->ledgerService->create($request->all());
           $request->merge([
                '_inventory_ledger' => $ledger,
            ]);
            return $this->success($ledger, Ledger::LEDGER_CREATED, 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function interAccountTransfer(InterAccountTransferRequest $request)
    {
        try {
           $user = $this->ledgerService->interAccountTransfer($request->all());
            return $this->success($user, Ledger::LEDGER_CREATED, 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    public function show($season_id,$id)
    {
        try {
            $user = $this->ledgerService->find($id);
            return $user ? $this->success($user) :
            $this->error(Ledger::UPDATE_RESTRICTED, 500);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(LedgerRequest $request,$season_id, $id)
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

            $fakeRequest = $this->ledgerService
                ->buildRequest($request->id);
            $res = $this->ledgerService->update(
                $fakeRequest,
                $request->id
            );

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
    public function activeSeason()
    {
        try {
            $activeSeason=$this->ledgerService->activeSeason();
            if($activeSeason)
                return $this->success($activeSeason, Ledger::ACTIVE_SEASON_SUCCESS);
            else
                return $this->success('', Ledger::ACTIVE_SEASON_FAILED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    public function receivablePayable(ReceivablePayableRequest $request,int $season_id)
    {
        try {
            $report=$this->ledgerService->getReceivablePayable($season_id,$request->all());
            return $this->success($report, Ledger::ACCOUNT_RECEIVABlLE_PAYABLE_SUCCESS);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
