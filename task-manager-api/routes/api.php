<?php

use App\Packages\Auth\Auth\Controllers\LoginController;
use App\Packages\Auth\Auth\Controllers\RegisterController;
use App\Packages\Auth\Auth\Controllers\LogoutController;
use App\Packages\Auth\Auth\Controllers\RefreshTokenController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'as' => 'v1.'], function () {

    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
        Route::post('register', [RegisterController::class, 'register'])->name('register');
        Route::post('login', [LoginController::class, 'login'])->name('login');
        Route::post('refresh', [RefreshTokenController::class, 'refresh'])->name('refresh');
        
        Route::group(['middleware' => 'auth.api'], function () {
            Route::post('logout', [LogoutController::class, 'logout'])->name('logout');
        });
    });

    Route::group(['middleware' => 'auth.api'], function () {
        Route::get('/health', function () {
            return response()->json([
                'status' => 'ok',
                'version' => 'v1',
                'timestamp' => now()->toIso8601String(),
            ]);
        })->name('health');
    });
});
