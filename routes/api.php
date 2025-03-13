<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware(\App\Http\Middleware\JwtMiddleware::class)->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
