<?php

namespace App\Services\Interfaces;

interface RoleServiceInterface extends BaseServiceInterface
{
    public function allPermissions();
}