<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarController;

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware([\App\Http\Middleware\JwtMiddleware::class, \App\Http\Middleware\CheckRole::class . ':customer'])->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('cars')->group(function () {
    // Public endpoints
    Route::get('', [CarController::class, 'index']);
    Route::get('/{id}', [CarController::class, 'show']);
    
    // Admin only endpoints
    Route::middleware([\App\Http\Middleware\JwtMiddleware::class, \App\Http\Middleware\CheckRole::class . ':admin'])->group(function () {
        Route::post('', [CarController::class, 'createCar']);
        Route::put('/{id}', [CarController::class, 'update']);
        Route::delete('/{id}', [CarController::class, 'destroy']);
    });
});
