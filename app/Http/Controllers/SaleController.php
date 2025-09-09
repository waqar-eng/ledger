<?php
namespace App\Http\Controllers;

use App\Services\Interfaces\SaleServiceInterface;
use App\Http\Requests\SaleRequest; 
use App\Models\Sale;
use Illuminate\Http\Request;

use Exception;

class SaleController extends Controller
{

    protected $saleService;

    public function __construct(SaleServiceInterface $saleService)
    {
        $this->saleService = $saleService;
    }

    public function index(Request $request) 
    {
        try {
            // Get start_date and end_date from the request
            $filters = $request->only(['start_date', 'end_date']);

            // Pass the filters to the service method for fetching sales
            $sales = $this->saleService->all($filters);

            return $this->success($sales);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(SaleRequest $request)
    {
        try {
            $sale = $this->saleService->create($request->all());
            return $this->success($sale, Sale::SALE_CREATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $sale = $this->saleService->find($id);
            return $this->success($sale);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(SaleRequest $request, $id)
    {
        try {
            $sale = $this->saleService->update($request->all(), $id);
            return $this->success($sale, Sale::SALE_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->saleService->delete($id);
            return $this->success(null, Sale::SALE_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}




