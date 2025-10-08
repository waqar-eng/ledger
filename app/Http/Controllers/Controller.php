<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use ApiResponseTrait, AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Dynamically authorize any model and action.
     *
     * @param string $action e.g. 'update', 'delete'
     * @param string $modelClass e.g. \App\Models\Ledger::class
     * @param int $id model ID
     */
    public function authorizeModelAction(string $action, string $modelClass, int $id)
    {
        $model = $modelClass::findOrFail($id);
        $this->authorize($action, $model);
    }
}
