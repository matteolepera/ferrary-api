<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Admin\StaffController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarModelController;
use App\Http\Controllers\Api\DesignerController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\EngineController;
use App\Http\Controllers\Api\VersionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// --- API pubblica: nessun login richiesto ---
Route::get('/models', [CarModelController::class, 'index']);

Route::get('/models/{carModel:slug}', [CarModelController::class, 'show']);

Route::get('/versions/{version:slug}', [VersionController::class, 'show']);

Route::get('/engines', [EngineController::class, 'index']);
Route::get('/engines/{engine}', [EngineController::class, 'show']);

Route::get('/designers', [DesignerController::class, 'index']);
Route::get('/designers/{designer:slug}', [DesignerController::class, 'show']);

Route::get('/drivers', [DriverController::class, 'index']);
Route::get('/drivers/{driver:slug}', [DriverController::class, 'show']);

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

        Route::get('/categories', [Admin\CategoryController::class, 'index']);
        Route::get('/categories/{category}', [Admin\CategoryController::class, 'show']);
        Route::post('/categories', [Admin\CategoryController::class, 'store']);
        Route::patch('/categories/{category}', [Admin\CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [Admin\CategoryController::class, 'destroy']);

        Route::get('/models', [Admin\CarModelController::class, 'index']);
        Route::get('/models/{carModel}', [Admin\CarModelController::class, 'show']);
        Route::post('/models', [Admin\CarModelController::class, 'store']);
        Route::patch('/models/{carModel}', [Admin\CarModelController::class, 'update']);
        Route::post('/models/{carModel}/publish', [Admin\PublicationController::class, 'publishModel']);
        Route::post('/models/{carModel}/unpublish', [Admin\PublicationController::class, 'unpublishModel']);
        Route::delete('/models/{carModel}', [Admin\CarModelController::class, 'destroy']);

        Route::get('/models/{carModel}/versions', [Admin\VersionController::class, 'index']);
        Route::get('/versions/{version}', [Admin\VersionController::class, 'show']);
        Route::post('/models/{carModel}/versions', [Admin\VersionController::class, 'store']);
        Route::patch('/versions/{version}', [Admin\VersionController::class, 'update']);
        Route::post('/versions/{version}/publish', [Admin\PublicationController::class, 'publishVersion']);
        Route::post('/versions/{version}/unpublish', [Admin\PublicationController::class, 'unpublishVersion']);
        Route::delete('/versions/{version}', [Admin\VersionController::class, 'destroy']);

        Route::get('/versions/{version}/racing', [Admin\VersionRacingController::class, 'index']);
        Route::post('/versions/{version}/racing', [Admin\VersionRacingController::class, 'store']);
        Route::patch('/racing/{racing}', [Admin\VersionRacingController::class, 'update']);
        Route::delete('/racing/{racing}', [Admin\VersionRacingController::class, 'destroy']);

        Route::apiResource('engines', Admin\EngineController::class);

        Route::apiResource('designers', Admin\DesignerController::class);

        Route::apiResource('drivers', Admin\DriverController::class);

        Route::put('/versions/{version}/designers', [Admin\VersionCreditsController::class, 'designers']);
        Route::put('/versions/{version}/drivers', [Admin\VersionCreditsController::class, 'drivers']);

        Route::get('/versions/{version}/images', [Admin\ImageController::class, 'index']);
        Route::post('/versions/{version}/images', [Admin\ImageController::class, 'store']);
        Route::patch('/images/{image}', [Admin\ImageController::class, 'update']);
        Route::put('/versions/{version}/images/order', [Admin\ImageController::class, 'reorder']);
        Route::delete('/images/{image}', [Admin\ImageController::class, 'destroy']);
    });

});
