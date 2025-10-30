<?php

namespace App\Http\Middleware;

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
            // Index or Show route allow without active season
        $allowedRoutes = ['ledgers.index', 'ledgers.show'];
        if (in_array($request->route()->getName(), $allowedRoutes)) {
            return $next($request);
        }

        $active = LedgerSeason::where('status','active')->first();
        if(!$active){
            return $this->error('No active season found. Please activate a season before creating ledger.', 403);
        }
        return $next($request);
    }
}
