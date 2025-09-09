<?php

namespace App\Http\Controllers;

use App\Http\Requests\Log_activityRequest;
use App\Models\Log_activity;
use App\Services\Interfaces\Log_activityServiceInterface;
use Exception;
use Illuminate\Http\Request;


class ActivityLogController extends Controller
{
    protected $log_activityService;

    public function __construct(Log_activityServiceInterface $log_activityService)
    {
        $this->log_activityService = $log_activityService;
    }

    public function index(Request $request)
    {
        try {
            $activity_log = $this->log_activityService->all();
            return $this->success($activity_log);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(Log_activityRequest $request)
    {
        try {
            $activity_log = $this->log_activityService->create($request->all());
            return $this->success($activity_log, Log_activity::LOG_CREATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $activity_log = $this->log_activityService->find($id);
            return $this->success($activity_log);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(Log_activityRequest $request, $id)
    {
        try {
            $activity_log = $this->log_activityService->update($request->all(), $id);
            return $this->success($activity_log, Log_activity::LOG_UPDATED);
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
