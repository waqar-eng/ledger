<?php

namespace App\Http\Middleware;

use App\AppEnum;
use App\Models\LedgerSeason;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSeason
{
    use ApiResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $seasonId = $request->route('season_id');
        $user = auth()->user();

        if ($user) {

            // Allow Super Admin to access any season
            if ($user->hasRole(AppEnum::SuperAdmin)) {
                return $next($request);
            }

            // Restrict normal users
            if ($user->season_id != $seasonId) {
                return $this->error('User does not belong to this season ', 403);
            }
        }

        return $next($request);
    }
}
