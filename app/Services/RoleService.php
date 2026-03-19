<?php

namespace App\Services;


use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Services\Interfaces\RoleServiceInterface;

class RoleService extends BaseService implements RoleServiceInterface
{
    protected $roleRepositoryInterface;
    /**
     * Create a new class instance.
     */
    public function __construct(RoleRepositoryInterface $roleRepositoryInterface)
    {
        parent::__construct($roleRepositoryInterface);
        $this->roleRepositoryInterface = $roleRepositoryInterface;
    }

}
