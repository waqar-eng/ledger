<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessRequest;
use App\Models\Business;
use App\Services\Interfaces\BusinessServiceInterface;
use Illuminate\Http\Request;
use Exception;

class BusinessController extends Controller
{
    protected $businessService;

    public function __construct(BusinessServiceInterface $businessService)
    {
        $this->businessService = $businessService;
    }

    public function index(Request $request)
    {
        try {
            $businesses = $this->businessService->all($request->all());

            return $this->success($businesses);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(BusinessRequest $request)
    {
        try {
            $business = $this->businessService->create($request->all());

            return $this->success($business, Business::BUSINESS_CREATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(BusinessRequest $request,$season_id,$id)
    {
        try {
            $business = $this->businessService->find($id);

            return $this->success($business);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(BusinessRequest $request, $season_id,$id)
    {
        try {
            $business = $this->businessService->update($request->all(), $id);

            return $this->success($business, Business::BUSINESS_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(BusinessRequest $request,$season_id,$id)
    {
        try {
            $this->businessService->delete($id);

            return $this->success(null, Business::BUSINESS_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}