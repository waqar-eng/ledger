<?php

namespace App\Services;

use App\AppEnum;
use App\Jobs\ProcessSeasonClosingJob;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\LedgerSeason;
use App\Models\SeasonSummary;
use App\Models\Stock;
use App\Repositories\Interfaces\LedgerSeasonRepositoryInterface;
use App\Services\Interfaces\LedgerSeasonServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerSeasonService extends BaseService implements LedgerSeasonServiceInterface
{
    protected $repository;

    public function __construct(LedgerSeasonRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
        public function search(array $filter){
            return $this->repository->search($filter);
        }
       public function updateSeason(array $data, $id)
        {
                $season = LedgerSeason::lockForUpdate()->findOrFail($id);

                $oldStatus = $season->status; // track previous status
                $newStatus = $data[AppEnum::STATUS->value] ?? $oldStatus;

                // Prevent re-closing an already completed season
                if ($oldStatus === AppEnum::Completed->value && $newStatus === AppEnum::Completed->value) {
                    throw new \Exception("This season is already completed.");
                }

                
                if ($newStatus === AppEnum::Completed->value) {
                    $start = Carbon::parse($season->start_date)->startOfDay();
                    $end   = Carbon::parse($season->end_date)->endOfDay();
                    $stockExists = Stock::whereBetween('created_at', [$start, $end])->where('total_quantity', '>', 0)->exists();
                    
                    dispatch(new ProcessSeasonClosingJob($season));
                    // Check payables
                    $payableExists = AccountPayable::where('balance', '>', 0)->whereBetween('created_at', [$start, $end])->exists();

                    // Check receivables
                    $receivableExists = AccountReceivable::where('balance', '>', 0)->whereBetween('created_at', [$start, $end])->exists();

                     if ($stockExists || $payableExists || $receivableExists) {
                        throw new \Exception(
                            "Season cannot be closed. Resolve all stock, payables and receivables before closing."
                        );
                    }
                    
                    $data[AppEnum::EndDate->value] = now();
                }
                $season->update($data);

                return $season->fresh();
        }
        public function season_summaries($id)
        {
            $seasonSummary= SeasonSummary::where('season_id', $id)->first();
            $seasonSummary->total_payables = json_decode($seasonSummary->total_payables, true);
            $seasonSummary->total_receivables = json_decode($seasonSummary->total_receivables, true);
            $seasonSummary->remaining_stock = json_decode($seasonSummary->remaining_stock, true);
            $seasonSummary->investor_breakdown = json_decode($seasonSummary->investor_breakdown, true);
            return $seasonSummary;
        }
}
