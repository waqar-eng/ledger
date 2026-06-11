<?php

namespace App\Services;


use App\Models\Sale;
use App\Repositories\Interfaces\BusinessRepositoryInterface;
use App\Services\Interfaces\BusinessServiceInterface;

class BusinessService extends BaseService implements BusinessServiceInterface
{
    protected $businessRepositoryInterface;
    /**
     * Create a new class instance.
     */
    public function __construct(BusinessRepositoryInterface $businessRepositoryInterface)
    {
        parent::__construct($businessRepositoryInterface);
        $this->businessRepositoryInterface = $businessRepositoryInterface;
    }


}
