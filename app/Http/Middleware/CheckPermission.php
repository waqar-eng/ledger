<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    use ApiResponseTrait;
    public function handle(Request $request, Closure $next)
    {
        if ($request->route() && $request->route()->getActionMethod() === 'getUserRolesPermissions') {
            return $next($request);
        }
        $user = auth()->user();

        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $action = $request->route()->getActionMethod(); // e.g., index, store, update
        $controller = class_basename($request->route()->getController()); // e.g., RoleController
        $model = strtolower(str_replace('Controller', '', $controller)); // e.g., role

        $permission = "$action $model"; // e.g., role.index, role.store
        
        if (!$user->can($permission)) {
            return $this->error('Forbidden: Missing permission ' . $permission, 403);
        }

        return $next($request);
    }
}
