<?php

use App\Packages\Auth\Auth\Controllers\LoginController;
use App\Packages\Auth\Auth\Controllers\RegisterController;
use App\Packages\Auth\Auth\Controllers\LogoutController;
use App\Packages\Auth\Auth\Controllers\RefreshTokenController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'as' => 'v1.'], function () {

    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
        Route::post('register', [RegisterController::class, 'register'])->middleware('throttle:10,1')->name('register');
        Route::post('login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login');
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

        Route::get('/health-auth', function () {
            return response()->json(['success' => true]);
        })->middleware('auth.api:admin.users.list')->name('health-auth');

        Route::group(['prefix' => 'social', 'as' => 'social.'], function () {
            Route::get('profile', [\App\Packages\Social\Person\Controllers\PersonController::class, 'show'])->name('profile.show');
            Route::put('profile', [\App\Packages\Social\Person\Controllers\PersonController::class, 'update'])->name('profile.update');
            Route::post('profile/avatar', [\App\Packages\Social\Person\Controllers\PersonController::class, 'avatar'])->name('profile.avatar');

            Route::get('contacts', [\App\Packages\Social\Contacts\Controllers\ContactController::class, 'index'])->name('contacts.index');
            Route::post('contacts', [\App\Packages\Social\Contacts\Controllers\ContactController::class, 'store'])->name('contacts.store');
            Route::put('contacts', [\App\Packages\Social\Contacts\Controllers\ContactController::class, 'update'])->name('contacts.update');
            Route::delete('contacts/{id}', [\App\Packages\Social\Contacts\Controllers\ContactController::class, 'destroy'])->name('contacts.destroy');
        });

        Route::group(['prefix' => 'tasks', 'as' => 'tasks.'], function () {
            Route::get('/', [\App\Packages\Task\Tasks\Controllers\TaskController::class, 'index'])->name('index');
            Route::post('/', [\App\Packages\Task\Tasks\Controllers\TaskController::class, 'store'])->name('store');
            Route::get('{id}', [\App\Packages\Task\Tasks\Controllers\TaskController::class, 'show'])->name('show');
            Route::put('{id}', [\App\Packages\Task\Tasks\Controllers\TaskController::class, 'update'])->name('update');
            Route::delete('{id}', [\App\Packages\Task\Tasks\Controllers\TaskController::class, 'destroy'])->name('destroy');
            Route::patch('{id}/status', [\App\Packages\Task\Tasks\Controllers\TaskController::class, 'updateStatus'])->name('update-status');
        });

        Route::get('task-statuses', [\App\Packages\Task\Statuses\Controllers\StatusController::class, 'index'])->name('task-statuses.index');
        Route::get('task-priorities', [\App\Packages\Task\Priorities\Controllers\PriorityController::class, 'index'])->name('task-priorities.index');

        Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
            Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
                Route::get('/', [\App\Packages\Admin\Users\Controllers\AdminUserController::class, 'index'])->middleware('auth.api:admin.users.list')->name('index');
                Route::get('{id}', [\App\Packages\Admin\Users\Controllers\AdminUserController::class, 'show'])->middleware('auth.api:admin.users.show')->name('show');
                Route::post('{id}/ban', [\App\Packages\Admin\Users\Controllers\AdminUserController::class, 'ban'])->middleware('auth.api:admin.users.ban')->name('ban');
                Route::post('{id}/activate', [\App\Packages\Admin\Users\Controllers\AdminUserController::class, 'activate'])->middleware('auth.api:admin.users.activate')->name('activate');
                Route::patch('{id}/role', [\App\Packages\Admin\Users\Controllers\AdminUserController::class, 'changeRole'])->middleware('auth.api:admin.users.role')->name('change-role');
            });
        });
    });
});
