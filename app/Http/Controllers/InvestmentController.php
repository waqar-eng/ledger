<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestmentRequest;
use App\Models\Investment;
use App\Services\Interfaces\InvestmentServiceInterface;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    use ApiResponseTrait;

    protected $investmentService;

    public function __construct(InvestmentServiceInterface $_investmentService)
    {
        $this->investmentService = $_investmentService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $ledger = $this->investmentService->All();
            return $this->success($ledger, Investment::INVESTMENT_RETRIVE_SUCCESS);
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
    public function store(InvestmentRequest $request)
    {
        try {
            // return $request->validated();
           $investment = $this->investmentService->create($request->validated());
            return $this->success($investment, Investment::INVESTMENT_SAVE_SUCCESS, 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Investment $investment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Investment $investment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Investment $investment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Investment $investment)
    {
        //
    }
}
