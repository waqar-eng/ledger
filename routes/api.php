<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\LedgerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvestmentController;

Route::prefix('v1')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::middleware('auth:api')->group(function () {

    });
    Route::resource('ledgers', LedgerController::class);
    Route::get('/ledgers/report', [LedgerController::class, 'report']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('customers', CustomerController::class);
    // Route::apiResource('sales', SaleController::class);
    // Route::apiResource('purchases', PurchaseController::class);
    // Route::apiResource('expenses', ExpenseController::class);
    Route::resource('investment', InvestmentController::class);
    Route::apiResource('/activity-logs', ActivityLogController::class);

});

