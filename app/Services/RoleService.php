<?php

namespace App\Services;


use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Services\Interfaces\RoleServiceInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
    public function all()
    {
        return Role::with('permissions')->get();
    }
    // Get all permissions
    public function allPermissions()
    {
        return Permission::all();
    }

    // Assign permissions to a role
    public function assignPermissionsToRole($roleId, array $permissions)
    {
        $role = Role::findOrFail($roleId);
        $role->syncPermissions($permissions); // safe sync
        return $role;
    }

    // Create role + assign permissions
    public function create(array $data)
    {
        // Create role via repository
        $role = $this->roleRepositoryInterface->create([
            'name' => $data['name']
        ]);

        // Assign permissions if any
        if (!empty($data['permissions'])) {
            $this->assignPermissionsToRole($role->id, $data['permissions']);
        }

        return $role;
    }

    // Update role + sync permissions
    public function update(array $data, $id)
    {
        // Update role via repository
        $role = $this->roleRepositoryInterface->update([
            'name' => $data['name']
        ], $id);

        // Sync permissions
        if (isset($data['permissions'])) {
            $this->assignPermissionsToRole($id, $data['permissions']);
        }

        return $role;
    }
}
