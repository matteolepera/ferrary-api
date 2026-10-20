<?php

use App\Http\Controllers\Api\Admin\StaffController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarModelController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::get('/models/{carModel:slug}', [CarModelController::class, 'show']);

// --- API pubblica: nessun login richiesto ---
Route::get('/models', [CarModelController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::prefix('admin')->group(function () {
        Route::get('/staff', [StaffController::class, 'index']);
        Route::get('/staff/{user}', [StaffController::class, 'show']);
        Route::post('/staff', [StaffController::class, 'store']);
        Route::patch('/staff/{user}', [StaffController::class, 'update']);
        Route::delete('/staff/{user}', [StaffController::class, 'destroy']);
        Route::patch('/staff/{user}/role', [StaffController::class, 'changeRole']);
        Route::post('/staff/{user}/transfer-master', [StaffController::class, 'transferMaster']);
    });

});
