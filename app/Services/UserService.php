<?php

namespace App\Services;


use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
class UserService extends BaseService implements UserServiceInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        parent::__construct($userRepositoryInterface);
    }
}

