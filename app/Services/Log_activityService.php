<?php

namespace App\Services;

use App\Models\Log_activity;
use App\Repositories\Interfaces\Log_activityRepositoryInterface;
use App\Services\Interfaces\Log_activityServiceInterface;

class Log_activityService extends BaseService implements Log_activityServiceInterface
{
    protected $purchaseRepositoryInterface;

    public function __construct(Log_activityRepositoryInterface $Log_activityRepositoryInterface)
    {
        parent::__construct($Log_activityRepositoryInterface);
        $this->purchaseRepositoryInterface = $Log_activityRepositoryInterface;
    }

    function all(array $filters = [])
    {
          $perPage = $filters['per_page'] ?? 10; 

          return Log_activity::latest()->paginate($perPage);
    }

}