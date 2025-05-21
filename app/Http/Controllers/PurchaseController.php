<?php
namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Services\Interfaces\PurchaseServiceInterface;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Exception;

class PurchaseController extends Controller
{
    use ApiResponseTrait;

    protected $purchaseService;

    public function __construct(PurchaseServiceInterface $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['start_date', 'end_date']);
            $purchases = $this->purchaseService->all($filters);
            return $this->success($purchases);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
      
        
    }

    public function store(PurchaseRequest $request)
    {
        try {
            $purchase = $this->purchaseService->create($request->all());
            return $this->success($purchase, 'Purchase created successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $purchase = $this->purchaseService->find($id);
            return $this->success($purchase);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(PurchaseRequest $request, $id)
    {
        try {
            $purchase = $this->purchaseService->update($request->all(), $id);
            return $this->success($purchase, 'Purchase updated successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->purchaseService->delete($id);
            return $this->success(null, 'Purchase deleted successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
