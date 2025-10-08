<?php

namespace App\Repositories;

use App\Models\AppSetting;
use App\Repositories\Interfaces\AppSettingRepositoryInterface;

class AppSettingRepository extends BaseRepository implements AppSettingRepositoryInterface
{
       public function __construct(AppSetting $model)
    {
        parent::__construct($model);
    }
}
