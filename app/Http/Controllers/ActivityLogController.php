<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index()
    {
        $logs = Activity::latest()->paginate(10); 
        return response()->json($logs);
    }

    /**
     * Display a specific activity log by ID.
     */
    public function show($id)
    {
        $log = Activity::find($id);

        if (!$log) {
            return response()->json(['message' => 'Log not found'], 404);
        }

        return response()->json($log);
    }

    /**
     * Remove the specified activity log from storage.
     */
    public function destroy($id)
    {
        $log = Activity::find($id);

        if (!$log) {
            return response()->json(['message' => 'Log not found'], 404);
        }

        $log->delete();

        return response()->json(['message' => 'Log deleted successfully']);
    }
}
