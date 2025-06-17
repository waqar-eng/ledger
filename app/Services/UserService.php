<?php

namespace App\Services;


use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Support\Facades\Auth;

class UserService extends BaseService implements UserServiceInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        parent::__construct($userRepositoryInterface);
    }
    public function loginUser($request)
    {
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
            $user = Auth::user();
            if ($user instanceof \App\Models\User) {
                return $user->createToken('API Token')->accessToken;
            } 
        }
        else {
           return false;
        }
    }
}

