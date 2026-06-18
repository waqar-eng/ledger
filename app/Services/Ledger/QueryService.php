<?php

namespace App\Services\Ledger;

use App\Constants\AppConstants;
use App\Models\Ledger;
use App\Models\LedgerSeason;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class QueryService
{
    public function __construct(
        private CalculationService $calculationService
    ) {}
    public function findAll(array $filters)
    {
        $perPage = $filters['per_page'] ?? AppConstants::DEFAULT_PER_PAGE;
        $page = $filters['page'] ?? 1;
        [$start_date, $end_date] = $this->parseDates($filters['start_date'] ?? '', $filters['end_date'] ?? '');

        $query = $this->buildQuery($filters, $start_date, $end_date)->withCount('adjustments');
        
        $paginated = $this->getFilteredTransactionsWithBalance($query , $page , $perPage);
        $allData = (clone $query)->get();
        $totals = $this->calculationService->calculateTotals($allData, $filters);

        return [
            'pagination' => $paginated,
            'totals' => $totals
        ];
    }

    private function buildQuery(array $filters, $start_date, $end_date)
    {
        $season_id= $filters['season_id'];
        if (!$season_id) {
            throw new \Exception(LedgerSeason::NO_ACTIVE_SEASON);
        }
        $query= Ledger::with(['user','category','purchase', 'investment', 'expense', 'sale', 'payment' ])
            ->where('ledger_season_id', $season_id)
                ->when($start_date && $end_date, fn($q) => $this->applyDateFilters($q, $start_date, $end_date))
                ->when(!empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
                ->when(!empty($filters['search_term']), fn($q) => $q->where('description', 'like', '%' . $filters['search_term'] . '%'))
                ->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
                ->when(!empty($filters['ledger_type']), fn($q) => $q->where('ledger_type', $filters['ledger_type']))
                ->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
                ->when(!empty($filters['bill_no']), fn($q) => $q->where('bill_no', $filters['bill_no']))

                ->when(!empty($filters['is_credit_sale']), function ($q) {
                    $q->where(function ($query) {
                        $query->where(function ($sub) {
                            $sub->where('ledger_type', 'sale');
                        })
                        ->orWhere(function ($sub) {
                            $sub->where('ledger_type', 'receive-payment');
                        });
                    });
                })
                ->when(!empty($filters['is_credit_purchase']), function ($q) {
                    $q->where(function ($query) {
                        $query->where(function ($sub) {
                            $sub->where('ledger_type', 'purchase');
                        })
                        ->orWhere(function ($sub) {
                            $sub->where('ledger_type', 'payment');
                        });
                    });
                })
                ->orderByDesc('id');
        return $query;
    }
    private function getFilteredTransactionsWithBalance($query, $page, $perPage)
    {
        $transactions = $query->get();
        $sortedForBalance  = $transactions->sortBy('id')->values();

        $runningBalance = 0;
        foreach ($sortedForBalance as $tran) {
            $amount = (float) $tran->amount;
            $runningBalance += $tran->amount;

            $original = $transactions->firstWhere('id', $tran->id);
            if ($original) {
                $original->calculated_balance = $runningBalance;
            }
        }

        $paginated = $transactions->forPage($page,$perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginated,
            $transactions->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
    public function applyDateFilters($query, $start_date, $end_date)
    {
        return $query->whereBetween('created_at', [$start_date, $end_date]);
    }

    public function parseDates($start, $end)
    {
        if (!$start || !$end) return [null, null];

        return [
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->endOfDay(),
        ];
    }

    public function paginate($query, $page, $perPage)
    {
        $data = $query->get()->values();

        return new LengthAwarePaginator(
            $data->forPage($page, $perPage),
            $data->count(),
            $perPage,
            $page
        );
    }

    public function totals($data)
    {
        return [
            'sale' => $data->where('ledger_type','sale')->sum('amount'),
            'purchase' => $data->where('ledger_type','purchase')->sum('amount'),
            'expense' => $data->where('ledger_type','expense')->sum('amount'),
        ];
    }

    public function getDashboardSummary($request): array
    {
        $now = Carbon::now();
        $season_id = $request['season_id'];

        $daily = Ledger::where('ledger_season_id', $season_id)->whereDate('created_at', $now->toDateString())->get();
        $monthly = Ledger::where('ledger_season_id', $season_id)->whereMonth('created_at', $now->month)->get();
        $yearly = Ledger::where('ledger_season_id', $season_id)->whereYear('created_at', $now->year)->get();
        $formatTotals = fn($collection) => [
            'sales' => $collection->where('ledger_type', 'sale')->sum('amount'),
            'expenses' => $collection->where('ledger_type', 'expense')->sum('amount'),
            'purchases' => $collection->where('ledger_type', 'purchase')->sum('amount'),
            'withdraw' => $collection->where('ledger_type', 'withdraw')->sum('amount'),
        ];

        return [
                'daily' => $formatTotals($daily),
                'monthly' => $formatTotals($monthly),
                'yearly' => $formatTotals($yearly),
        ];
    }
}