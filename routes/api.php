<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarController;

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('google', [AuthController::class, 'googleLogin']);
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);

    // Protected routes
    Route::middleware([\App\Http\Middleware\JwtMiddleware::class])->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('cars')->group(function () {
    // Public endpoints
    Route::get('', [CarController::class, 'getAllCars']);
    Route::get('/{id}', [CarController::class, 'getCarByID']);

    // Admin only endpoints
    Route::middleware([\App\Http\Middleware\JwtMiddleware::class, \App\Http\Middleware\CheckRole::class . ':admin'])->group(function () {
        Route::post('', [CarController::class, 'createCar']);
        Route::put('/{id}', [CarController::class, 'update']);
        Route::delete('/{id}', [CarController::class, 'destroy']);
    });
});

Route::prefix('upload')->group(function () {
    Route::post('file', [\App\UploadService\UploaderService::class, 'uploadFile']);
});
