<?php

namespace App\Http\Controllers;

use App\Models\Log_activity;
use App\Services\Interfaces\Log_activityServiceInterface;
use Exception;

class ActivityLogController extends Controller
{
    protected $log_activityService;

    public function __construct(Log_activityServiceInterface $log_activityService)
    {
        $this->log_activityService = $log_activityService;
    }

    public function index()
    {
        try {
            $activity_log = $this->log_activityService->all();
            return $this->success($activity_log);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->log_activityService->delete($id);
            return $this->success(null, Log_activity::LOG_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
  
        }
    }

}
