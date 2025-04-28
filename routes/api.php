<?php

use Illuminate\Support\Facades\Route;

// Route::prefix('ledger')->group(function () {
//     Route::get('/', [LedgerController::class, 'index']);
//     Route::post('/', [LedgerController::class, 'store']);
//     Route::get('/{id}', [LedgerController::class, 'show']);
//     Route::put('/{id}', [LedgerController::class, 'update']);
//     Route::delete('/{id}', [LedgerController::class, 'destroy']);
// });

Route::prefix('v1')->group(function () {
    Route::resource('ledger', 'App\Http\Controllers\LedgerController');
});
