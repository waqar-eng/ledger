<?php

namespace App\Services;

use App\AppEnum;
use App\Models\LedgerSeason;
use App\Repositories\Interfaces\LedgerSeasonRepositoryInterface;
use App\Services\Interfaces\LedgerSeasonServiceInterface;
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
            return DB::transaction(function () use ($data, $id) {
                $season = LedgerSeason::findOrFail($id);

                if (
                    ($data[AppEnum::STATUS->value] ?? $season->status) === AppEnum::Active->value &&
                    ($data[AppEnum::EndDate->value] ?? $season->end_date) &&
                    now()->gt($data[AppEnum::EndDate->value] ?? $season->end_date)
                ) {
                    $data[AppEnum::STATUS->value] = AppEnum::Completed->value;
                }

                $season->update($data);
                return $season;
            });
        }
}
