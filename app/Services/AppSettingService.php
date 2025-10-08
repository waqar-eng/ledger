<?php

namespace App\Services;

use App\Repositories\Interfaces\AppSettingRepositoryInterface;
use App\Services\Interfaces\AppSettingServiceInterface;

class AppSettingService extends BaseService implements AppSettingServiceInterface
{
    public function __construct(AppSettingRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

}
