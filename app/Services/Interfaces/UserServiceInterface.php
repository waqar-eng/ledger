<?php

namespace App\Services\Interfaces;

interface UserServiceInterface extends BaseServiceInterface
{
    public function loginUser($request);
    public function findAll(array $filters);
    public function update($request, $id);
    public function userDetail($request);
}
