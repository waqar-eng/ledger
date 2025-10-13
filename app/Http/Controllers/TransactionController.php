<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use App\Services\Interfaces\TransactionServiceInterface;
use Exception;
use Illuminate\Http\Request;

class TransactionController extends Controller

{
    protected $transactionService;

    public function __construct(TransactionServiceInterface $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        try {
            $transactions = $this->transactionService->findAll($request->all());
            return $this->success($transactions,Transaction::TRANSACTIONS_FETCHED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(TransactionRequest $request)
    {
        try {
            $transaction = $this->transactionService->create($request->validated());
            return $this->success($transaction, Transaction::TRANSACTION_CREATED, 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(TransactionRequest $request)
    {
        try {
            $transaction = $this->transactionService->find($request->id);
            return $this->success($transaction, Transaction::TRANSACTION_FETCHED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    public function getTransactionSummary()
    {
        try {
            $transactionSummary = $this->transactionService->getTransactionSummary();
            return $this->success($transactionSummary, Transaction::TRANSACTION_SUMMARY_FETCHED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(TransactionRequest $request)
    {
        try {
            $transaction = $this->transactionService->update( $request->validated() ,$request->id);
            return $this->success( $transaction, Transaction::TRANSACTION_UPDATED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(TransactionRequest $request)
    {
        try {
             $delete = $this->transactionService->delete($request->id);
            return $this->success($delete, Transaction::TRANSACTION_DELETED);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
