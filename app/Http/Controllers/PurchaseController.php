<?php
namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Services\Interfaces\PurchaseServiceInterface;
use Illuminate\Http\Request;
use Exception;

class PurchaseController extends Controller
{

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
            return $this->success($purchase, Purchase::PURCHASE_CREATED);
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
            return $this->success($purchase, Purchase::PURCHASE_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->purchaseService->delete($id);
            return $this->success(null, Purchase::PURCHASE_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
