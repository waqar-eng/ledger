<?php

namespace App\Http\Controllers;

use App\Http\Requests\LedgerSeasonRequest;
use App\Models\LedgerSeason;
use App\Services\Interfaces\LedgerSeasonServiceInterface;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;

class LedgerSeasonController extends Controller
{
    use ApiResponseTrait;
    protected $ledgerSeasonService;

    public function __construct(LedgerSeasonServiceInterface $ledgerSeasonService)
    {
        $this->ledgerSeasonService = $ledgerSeasonService;
    }

    public function index(Request $request)
    {
          try {
            $season = $this->ledgerSeasonService->all($request->all());
            return $this->success($season , LedgerSeason::LEDGER_SEASONS_RETRIEVED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(LedgerSeasonRequest $request)
    {
         try {

           $season = $this->ledgerSeasonService->create($request->all());
            return $this->success($season, LedgerSeason::LEDGER_SEASON_CREATED, 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
         try {
            $season = $this->ledgerSeasonService->find($id);
            return $this->success($season , LedgerSeason::LEDGER_SEASON_RETRIEVED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function search(Request $request)
    {
         try {
            $filter = $request->all();
            $season = $this->ledgerSeasonService->search($filter);
            return $this->success($season );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(LedgerSeasonRequest $request ,$id)
    {
         try {
            $season = $this->ledgerSeasonService->updateSeason($request->all(), $id);
            return $this->success($season, LedgerSeason::LEDGER_SEASON_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
         try {
            $res=$this->ledgerSeasonService->delete($id);
            return $this->success($res, LedgerSeason::LEDGER_SEASON_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
