<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Exception;

use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    public function index(UserRequest $request)
    {
        try {
            $users = $this->userService->findAll($request->all());
            return $this->success($users);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    public function AllUsers(UserRequest $request)
    {
        try {
            $users = $this->userService->AllUsers($request->all());
            return $this->success($users);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(UserRequest $request)
    {
        try {
            $user = $this->userService->create($request->all());
            return $this->success($user, User::USER_CREATED, 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $user = $this->userService->find($id);
            return $this->success($user);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(UserRequest $request, $id)
    {
        try {
            $this->authorizeModelAction('update', User::class, $id);
            $request = $request->validated();
            $user = $this->userService->update($request, $id);
            return $this->success($user, User::USER_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->authorizeModelAction('delete', User::class, $id);
            $this->userService->delete($id);
            return $this->success(null, User::USER_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    public function login(Request $request)
    {
        try {
            $userDetails = $this->userService->loginUser($request);
            if($userDetails) {
                return $this->success(['token' => $userDetails['token'], 'user'=>$userDetails['user']], User::LOGIN_SUCCESS);
            }
            else{
                return $this->error(User::LOGIN_ERROR, 500);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    
   public function userDetails(Request $request)
   {
    try {
        $users = $this->userService->userDetail($request);
        if($users){
            return $this->success(['users'=> $users], User::USERS_FETCHED);
        }
        else{
            return $this->error(User::USERS_FETCHED_ERROR);
        }
    }catch (Exception $e){
        return $this->error($e->getMessage(),500);
    }

   }

}
