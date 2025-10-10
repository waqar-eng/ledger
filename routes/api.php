<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::prefix('v1')->group(function () {
    //only login route public
    Route::post('/login', [UserController::class, 'login']);

    Route::middleware('auth:api')->group(function () {

        Route::get('user-details',[UserController::class, 'userDetails']);
        Route::resource('users', UserController::class);
        Route::resource('/activity-logs', ActivityLogController::class);
        Route::apiResource('transactions', TransactionController::class);
        Route::resource('/categories', CategoryController::class);
        Route::resource('customers', CustomerController::class);

    });
});
