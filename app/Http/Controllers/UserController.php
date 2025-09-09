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

    public function store(UserRequest $request)
    {
        try {
            $user = $this->userService->create($request->validated());
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
            $user = $this->userService->update($request->validated(), $id);
            return $this->success($user, User::USER_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->userService->delete($id);
            return $this->success(null, User::USER_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    public function login(Request $request)
    {
        try {
            $token = $this->userService->loginUser($request);
            if($token) {
                return $this->success(['token' => $token], User::LOGIN_SUCCESS);
            }
            else{
                return $this->error(User::LOGIN_ERROR, 500);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
