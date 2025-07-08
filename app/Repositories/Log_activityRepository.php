<?php

namespace App\Repositories;

use App\Models\Log_activity;
use App\Repositories\Interfaces\Log_activityRepositoryInterface;

class Log_activityRepository extends BaseRepository implements Log_activityRepositoryInterface
{
    public function __construct(Log_activity $model)
    {
        parent::__construct($model);
    }
}
