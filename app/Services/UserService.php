<?php

namespace App\Services;


use App\Models\User;
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
    
    public function findAll(array $filters)
    {
        $perPage = $filters['per_page'] ?? 10;
        $search = $filters['search'] ?? '';

        return User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
                    
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
   }
}

