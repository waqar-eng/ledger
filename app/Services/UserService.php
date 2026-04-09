<?php

namespace App\Services;

use App\Models\LedgerSeason;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
            // Flush cached permissions
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            if ($user instanceof \App\Models\User) {
                return ['token'=>$user->createToken('API Token')->accessToken, 'user'=>$user];
            }
        }
        else {
           return false;
        }
    }
    public function userRolesPermissions()
    {
        $user = auth()->user()->load('roles.permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $user->type,

            // roles
            'roles' => $user->roles->pluck('name'),

            // permissions (from roles)
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }
    public function findAll(array $filters)
    {
        $perPage = $filters['per_page'] ?? 10;
        $search = $filters['search'] ?? '';
        $season = LedgerSeason::getActiveSeason();
        $start = $season->start_date ?? '';
        $end   = $season->end_date ?? '';

        $query = User::with([
            'accountReceivables' => fn($q) => $q->whereBetween('created_at', [$start, $end]),
            'accountPayables' => fn($q) => $q->whereBetween('created_at', [$start, $end]),
            'ledgers' => fn($q) => $q->whereBetween('created_at', [$start, $end]),
            'sales.ledger' => fn($q) => $q->whereBetween('created_at', [$start, $end]),
            'purchases.ledger' => fn($q) => $q->whereBetween('created_at', [$start, $end]),
            'expenses.ledger' => fn($q) => $q->whereBetween('created_at', [$start, $end]),
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%");
                });
            })->orderByDesc('id');
        return $perPage ? $query->paginate($perPage) : $query->get();
   }
    public function AllUsers($filters)
    {
        $search = $filters['search'] ?? '';
        $season = LedgerSeason::getActiveSeason();
        $start = $season->start_date ?? '';
        $end   = $season->end_date ?? '';
        return User::with([
            'accountReceivables' => fn($q) => $q->whereBetween('created_at', [$start, $end]),
            'accountPayables' => fn($q) => $q->whereBetween('created_at', [$start, $end]),
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%");

                });
            })
            ->orderByDesc('id')->get();
    }
    public function create($request)
    {
        $roleId = $request['role_id'] ?? null;
        unset($request['role_id']);
        $user = parent::create($request);
        if ($user && $roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $user->assignRole($role->name);
            }
        }
        return $user ? $user : [];
    }
    public function update($request, $id)
    {
        unset($request['email']);
        if(!$request['password'])
            unset($request['password']);
        $roleId = $request['role_id'] ?? null;
        unset($request['role_id']);
        $user = parent::update($request, $id);
        if ($user && $roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $user->syncRoles([$role->name]);
            }
        }
        return $user ? $user : [];
    }

    public function show($id){
        $user = User::with('roles')->find($id);
        return $user;
    }
   public function userDetail($request){
     $user = Auth::guard('api')->user();
     return $user;
   }

}
