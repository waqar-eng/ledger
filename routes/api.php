<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\LedgerSeasonController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockController;
use App\Http\Middleware\LoggingMiddleware;

Route::prefix('v1')->group(function () {
    //only login route public
    Route::post('/login', [UserController::class, 'login']);

    Route::middleware(['auth:api',"check_permission"])->group(function () {

        Route::get('user-details',[UserController::class, 'userDetails']);
        Route::get('seasons/{season_id}/dashboard-summary', [LedgerController::class, 'dashboardSummary']);
        Route::get('/ledgers/reports', [LedgerController::class, 'report']);
        Route::get('ledgers/bill-number', [LedgerController::class, 'billNumber']);
        Route::get('active-season', [LedgerController::class, 'activeSeason']);
        Route::middleware('check.active.season')->group(function (){
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user_id}', [UserController::class, 'show']);
            Route::put('/users/{user_id}', [UserController::class, 'update']);
            Route::delete('/users/{user_id}', [UserController::class, 'destroy']);
            Route::post('/users', [UserController::class, 'store']);

            //expense-type
            Route::get('/expense-type', [ExpenseTypeController::class, 'index']);
            Route::get('/expense-type/{expense_type}', [ExpenseTypeController::class, 'show']);
            Route::put('/expense-type/{expense_type}', [ExpenseTypeController::class, 'update']);
            Route::delete('/expense-type/{expense_type}', [ExpenseTypeController::class, 'destroy']);
            Route::post('/expense-type', [ExpenseTypeController::class, 'store']);

            //categories
            Route::get('/categories', [CategoryController::class, 'index']);
            Route::get('/categories/{category}', [CategoryController::class, 'show']);
            Route::put('/categories/{category}', [CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
            Route::post('/categories', [CategoryController::class, 'store']);

            //roles
            Route::get('/roles', [RoleController::class, 'index']);
            Route::get('/roles/{role_id}', [RoleController::class, 'show']);
            Route::put('/roles/{role_id}', [RoleController::class, 'update']);
            Route::delete('/roles/{role_id}', [RoleController::class, 'destroy']);
            Route::post('/roles', [RoleController::class, 'store']);

            //ledgers
            Route::get('seasons/{season_id}/ledgers', [LedgerController::class, 'index']);
            Route::get('seasons/{season_id}/ledgers/{ledger_id}', [LedgerController::class, 'show']);
            Route::put('seasons/{season_id}/ledgers/{ledger_id}', [LedgerController::class, 'update']);
            Route::delete('seasons/{season_id}/ledgers/{ledger_id}', [LedgerController::class, 'destroy']);
            Route::middleware(LoggingMiddleware::class)->group(function () {
                Route::post('seasons/{season_id}/ledgers', [LedgerController::class, 'store']);
            });
            Route::post('seasons/{season_id}/inter-account-transfer', [LedgerController::class, 'interAccountTransfer']);

            //stocks
            Route::get('/stocks', [StockController::class, 'index']);
            Route::get('/stocks/{ledger_id}', [StockController::class, 'show']);
            Route::put('/stocks/{ledger_id}', [StockController::class, 'update']);
            Route::delete('/stocks/{ledger_id}', [StockController::class, 'destroy']);
            Route::post('/stocks', [StockController::class, 'store']);

            Route::get('/businesses', [BusinessController::class, 'index']);
            Route::get('/businesses/{business_id}', [BusinessController::class, 'show']);
            Route::put('/businesses/{business_id}', [BusinessController::class, 'update']);
            Route::delete('/businesses/{business_id}', [BusinessController::class, 'destroy']);
            Route::post('/businesses', [BusinessController::class, 'store']);

            Route::get('/accounts', [AccountController::class, 'index']);
            Route::get('/accounts/{account_id}', [AccountController::class, 'show']);
            Route::get('seasons/{season_id}/account/{account_id}/transactions', [AccountController::class, 'showAccountTransactions']);
            Route::put('/accounts/{account_id}', [AccountController::class, 'update']);
            Route::delete('/accounts/{account_id}', [AccountController::class, 'destroy']);
            Route::post('/accounts', [AccountController::class, 'store']);

            Route::get('seasons/{season_id}/receivable-payable',[LedgerController::class, 'receivablePayable']);

            Route::get('seasons/{season_id}/all-users', [UserController::class,'AllUsers']);
        });
        Route::get('current-user-permissions', [UserController::class,'getUserRolesPermissions']);
         Route::apiResource('sales', SaleController::class);
        // Route::apiResource('purchases', PurchaseController::class);
        // Route::apiResource('expenses', ExpenseController::class);
        Route::resource('investment', InvestmentController::class);
        Route::resource('/activity-logs', ActivityLogController::class);
        Route::resource('app-settings', AppSettingController::class);

        Route::get('season-search', [LedgerSeasonController::class, 'search']);
        Route::apiResource('ledger-seasons', LedgerSeasonController::class);
        Route::get('season-summaries/{season_id}', [LedgerSeasonController::class, 'season_summaries']);
        Route::get('/permissions', [RoleController::class,'allPermissions']);
    });


});
