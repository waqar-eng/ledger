<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppSettingController;
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

Route::prefix('v1')->group(function () {
    //only login route public
    Route::post('/login', [UserController::class, 'login']);

    Route::middleware(['auth:api',"check_permission"])->group(function () {

        Route::get('user-details',[UserController::class, 'userDetails']);
        Route::get('season/{season_id}/dashboard-summary', [LedgerController::class, 'dashboardSummary']);
        Route::get('/ledgers/reports', [LedgerController::class, 'report']);
        Route::get('ledgers/bill-number', [LedgerController::class, 'billNumber']);
        Route::get('active-season', [LedgerController::class, 'activeSeason']);
        Route::middleware('check.active.season')->group(function (){
            Route::get('seasons/{season_id}/users', [UserController::class, 'index']);
            Route::get('seasons/{season_id}/users/{user_id}', [UserController::class, 'show']);
            Route::put('seasons/{season_id}/users/{user_id}', [UserController::class, 'update']);
            Route::delete('seasons/{season_id}/users/{user_id}', [UserController::class, 'destroy']);
            Route::post('seasons/{season_id}/users', [UserController::class, 'store']);

            //expense-type
            Route::get('seasons/{season_id}/expense-type', [ExpenseTypeController::class, 'index']);
            Route::get('seasons/{season_id}/expense-type/{expense_type}', [ExpenseTypeController::class, 'show']);
            Route::put('seasons/{season_id}/expense-type/{expense_type}', [ExpenseTypeController::class, 'update']);
            Route::delete('seasons/{season_id}/expense-type/{expense_type}', [ExpenseTypeController::class, 'destroy']);
            Route::post('seasons/{season_id}/expense-type', [ExpenseTypeController::class, 'store']);

            //categories
            Route::get('seasons/{season_id}/categories', [CategoryController::class, 'index']);
            Route::get('seasons/{season_id}/categories/{category}', [CategoryController::class, 'show']);
            Route::put('seasons/{season_id}/categories/{category}', [CategoryController::class, 'update']);
            Route::delete('seasons/{season_id}/categories/{category}', [CategoryController::class, 'destroy']);
            Route::post('seasons/{season_id}/categories', [CategoryController::class, 'store']);

            //roles
            Route::get('seasons/{season_id}/roles', [RoleController::class, 'index']);
            Route::get('seasons/{season_id}/roles/{role_id}', [RoleController::class, 'show']);
            Route::put('seasons/{season_id}/roles/{role_id}', [RoleController::class, 'update']);
            Route::delete('seasons/{season_id}/roles/{role_id}', [RoleController::class, 'destroy']);
            Route::post('seasons/{season_id}/roles', [RoleController::class, 'store']);
            
            //ledgers
            Route::get('seasons/{season_id}/ledgers', [LedgerController::class, 'index']);
            Route::get('seasons/{season_id}/ledgers/{ledger_id}', [LedgerController::class, 'show']);
            Route::put('seasons/{season_id}/ledgers/{ledger_id}', [LedgerController::class, 'update']);
            Route::delete('seasons/{season_id}/ledgers/{ledger_id}', [LedgerController::class, 'destroy']);
            Route::post('seasons/{season_id}/ledgers', [LedgerController::class, 'store']);

            //stocks
            Route::get('seasons/{season_id}/stocks', [StockController::class, 'index']);
            Route::get('seasons/{season_id}/stocks/{ledger_id}', [StockController::class, 'show']);
            Route::put('seasons/{season_id}/stocks/{ledger_id}', [StockController::class, 'update']);
            Route::delete('seasons/{season_id}/stocks/{ledger_id}', [StockController::class, 'destroy']);
            Route::post('seasons/{season_id}/stocks', [StockController::class, 'store']);


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
        Route::get('seasons/{season_id}/permissions', [RoleController::class,'allPermissions']);
    });


});
