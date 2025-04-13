<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CartController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\PaymentController;

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('google', [AuthController::class, 'googleLogin']);
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);
    Route::post('verify-email/{token}', [AuthController::class, 'verifyEmail']);
    Route::post('resend-verification-email', [AuthController::class, 'resendVerificationToken']);
    Route::post('send-reset-password-email', [AuthController::class, 'sendPasswordResetToken']);
    Route::post('reset-password/{token}', [AuthController::class, 'resetPassword']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    // Protected routes
    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('cars')->group(function () {
    // Public endpoints
    Route::get('', [CarController::class, 'getAllCars']);
    Route::get('/newest', [CarController::class, 'getNewestCar']);
    Route::get('/{id}', [CarController::class, 'getCarByID']);

    // Admin only endpoints
    Route::middleware([JwtMiddleware::class, CheckRole::class . ':admin'])->group(function () {
        Route::post('', [CarController::class, 'createCar']);
        Route::post('/{id}', [CarController::class, 'updateCar']);
        Route::delete('/{id}', [CarController::class, 'deleteCar']);
    });
});

Route::prefix('upload')->group(function () {
    Route::post('file', [\App\UploadService\UploaderService::class, 'uploadFile']);
});

Route::prefix('brands')->group(function () {
    // Public endpoints
    Route::get('/', [BrandController::class, 'getAllBrands']);
    Route::get('/{id}', [BrandController::class, 'getBrandByID']);

    Route::middleware([JwtMiddleware::class, CheckRole::class . ':admin'])->group(function () {
        // Admin only endpoints
        Route::post('/', [BrandController::class, 'createBrand']);
        Route::post('/{id}', [BrandController::class, 'updateBrand']);
        Route::delete('/{id}', [BrandController::class, 'deleteBrand']);
    });
});

Route::prefix('cart')->group(function () {
    // Private endpoints
    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::get('/me', [CartController::class, 'getCartByUserId']);
        Route::post('/add', [CartController::class, 'addToCart']);
        Route::delete('/remove', [CartController::class, 'removeFromCart']);
        Route::delete('/clear', [CartController::class, 'clearCart']);
    });
});

Route::prefix('order')->group(function() {
    // Private endpoints
    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::get('/me', [OrderController::class, 'getOrders']);
        Route::get('/{id}', [OrderController::class, 'getOrderDetails']);
        Route::post('/create', [OrderController::class, 'createOrder']);
        Route::delete('/cancel/{id}', [OrderController::class, 'cancelOrder']);
    });

    // Admin only endpoints
    Route::middleware([JwtMiddleware::class, CheckRole::class . ':admin'])->group(function () {
        Route::get('/', [OrderController::class, 'getAllOrders']);
    });
});

Route::prefix('payment')->group(function () {
    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::post('/create-link', [PaymentController::class, 'createPaymentLink']);
        Route::get('/check-status/{orderCode}', [PaymentController::class, 'checkPaymentStatus']);
    });
});