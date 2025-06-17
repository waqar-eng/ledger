<?php

namespace App\Services\Interfaces;

interface UserServiceInterface extends BaseServiceInterface
{
    public function loginUser($request);
}
