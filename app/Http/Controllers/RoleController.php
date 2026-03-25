<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interfaces\RoleServiceInterface;
use App\Http\Requests\RoleRequest;
use Exception;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleServiceInterface $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index(Request $request)
    {
        try {
            $roles = $this->roleService->all($request->all());
            return $this->success($roles);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(RoleRequest $request)
    {
        try {
            $role = $this->roleService->create($request->all());
            return $this->success($role, 'Role created successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $role = $this->roleService->find($id);
            return $this->success($role);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(RoleRequest $request, $id)
    {
        try {
            $role = $this->roleService->update($request->all(), $id);
            return $this->success($role, 'Role updated successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function allPermissions(Request $request)
    {
        try {
            $roles = $this->roleService->allPermissions();
            return $this->success($roles);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->roleService->delete($id);
            return $this->success(null, 'Role deleted successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}