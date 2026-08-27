<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DrinkController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PreferenceController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::get('drinks', [DrinkController::class, 'index']);
Route::get('drinks/{drink}', [DrinkController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('preferences', [PreferenceController::class, 'show']);
    Route::put('preferences', [PreferenceController::class, 'update']);

    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/history', [OrderController::class, 'history']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::post('ratings', [RatingController::class, 'store']);
    Route::get('ratings', [RatingController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('drinks', [DrinkController::class, 'adminIndex']);
    Route::get('drinks/{drink}', [DrinkController::class, 'adminShow']);
    Route::post('drinks', [DrinkController::class, 'store']);
    Route::put('drinks/{drink}', [DrinkController::class, 'update']);
    Route::delete('drinks/{drink}', [DrinkController::class, 'destroy']);

    Route::get('orders', [OrderController::class, 'adminIndex']);
    Route::get('orders/{order}', [OrderController::class, 'adminShow']);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);

    Route::get('reports/best-selling-drinks', [ReportController::class, 'bestSellingDrinks']);
    Route::get('reports/recommendation-effectiveness', [ReportController::class, 'recommendationEffectiveness']);
});
