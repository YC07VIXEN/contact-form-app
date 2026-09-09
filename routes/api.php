<?php

use App\Http\Controllers\Api\ContactApiController;
use Illuminate\Support\Facades\Route;

// テスト要件（api/v1/...）に準拠させる
Route::prefix('v1')->group(function () {
    Route::get('/contacts', [ContactApiController::class, 'index']);
    Route::get('/contacts/{id}', [ContactApiController::class, 'show']);
    Route::post('/contacts', [ContactApiController::class, 'store']);
    Route::put('/contacts/{id}', [ContactApiController::class, 'update']);
    Route::delete('/contacts/{id}', [ContactApiController::class, 'destroy']);
});
