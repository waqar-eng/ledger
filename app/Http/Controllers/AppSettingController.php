<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppSettingRequest;
use App\Models\AppSetting;
use App\Services\AppSettingService;
use App\Services\Interfaces\AppSettingServiceInterface;
use Exception;

class AppSettingController extends Controller
{
    protected AppSettingService $appSettingService;

    public function __construct(AppSettingServiceInterface $appSettingService)
    {
        $this->appSettingService = $appSettingService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $ledger = $this->appSettingService->all();
            return $this->success($ledger);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AppSettingRequest $request)
    {
        try {
            $setting = $this->appSettingService->create($request->all());
             return $this->success($setting, AppSetting::APP_SETTING_CREATED, 201);
         } catch (Exception $e) {
             return $this->error($e->getMessage(), 500);
         }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AppSettingRequest $request)
    {
        try {
            $ledger = $this->appSettingService->update($request->all(), $request->id);
            return $ledger
                ? $this->success($ledger, AppSetting::APP_SETTING_UPDATED)
                : $this->error(AppSetting::APP_SETTING_ERROR, 500);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
