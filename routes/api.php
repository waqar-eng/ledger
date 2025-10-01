<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\StockController;

Route::prefix('v1')->group(function () {
    //only login route public
    Route::post('/login', [UserController::class, 'login']);

    Route::middleware('auth:api')->group(function () {

        Route::get('user-details',[UserController::class, 'userDetails']);
        Route::get('/ledgers/dashboard-summary', [LedgerController::class, 'dashboardSummary']);
        Route::get('/ledgers/reports', [LedgerController::class, 'report']);
        Route::get('ledgers/bill-number', [LedgerController::class, 'billNumber']);
        Route::resource('ledgers', LedgerController::class);
        Route::resource('users', UserController::class);
        Route::get('all-users', [UserController::class,'AllUsers']);
        Route::resource('customers', CustomerController::class);
         Route::apiResource('sales', SaleController::class);
        // Route::apiResource('purchases', PurchaseController::class);
        // Route::apiResource('expenses', ExpenseController::class);
        Route::resource('investment', InvestmentController::class);
        Route::resource('/activity-logs', ActivityLogController::class);
        Route::resource('/categories', CategoryController::class);
        Route::resource('stocks', StockController::class);

    });

});
