<?php

namespace App\Repositories;

use App\Models\LedgerSeason;
use App\Repositories\Interfaces\LedgerSeasonRepositoryInterface;

class LedgerSeasonRepository extends BaseRepository implements LedgerSeasonRepositoryInterface
{
    public function __construct(LedgerSeason $model)
    {
        parent::__construct($model);
    }

  public function search(array $filters)
{
    $query = LedgerSeason::query();

    $query->when(!empty($filters['search_term']), function ($q) use ($filters) {
        $term = $filters['search_term'];
        $q->where(function ($q2) use ($term) {
            $q2->where('name', 'like', "%{$term}%")
               ->orWhere('description', 'like', "%{$term}%")
               ->orWhere('status', 'like', "%{$term}%");
        });
    });

    return $query->get();
}


}
