<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DrinkController;
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

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('drinks', [DrinkController::class, 'adminIndex']);
    Route::get('drinks/{drink}', [DrinkController::class, 'adminShow']);
    Route::post('drinks', [DrinkController::class, 'store']);
    Route::put('drinks/{drink}', [DrinkController::class, 'update']);
    Route::delete('drinks/{drink}', [DrinkController::class, 'destroy']);
});
