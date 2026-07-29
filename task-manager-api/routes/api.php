<?php

use App\Packages\Admin\AuditLogs\Controllers\AuditLogController;
use App\Packages\Admin\Permissions\Controllers\PermissionController;
use App\Packages\Admin\Roles\Controllers\RoleController;
use App\Packages\Admin\Users\Controllers\AdminUserController;
use App\Packages\Auth\Auth\Controllers\LoginController;
use App\Packages\Auth\Auth\Controllers\LogoutController;
use App\Packages\Auth\Auth\Controllers\RefreshTokenController;
use App\Packages\Auth\Auth\Controllers\RegisterController;
use App\Packages\Social\Contacts\Controllers\ContactController;
use App\Packages\Social\Person\Controllers\PersonController;
use App\Packages\Task\Priorities\Controllers\PriorityController;
use App\Packages\Task\Statuses\Controllers\StatusController;
use App\Packages\Task\Tasks\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'as' => 'v1.', 'middleware' => 'throttle:api'], function () {

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
            Route::get('profile', [PersonController::class, 'show'])->name('profile.show');
            Route::put('profile', [PersonController::class, 'update'])->name('profile.update');
            Route::post('profile/avatar', [PersonController::class, 'avatar'])->name('profile.avatar');

            Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
            Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
            Route::put('contacts', [ContactController::class, 'update'])->name('contacts.update');
            Route::delete('contacts/{id}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        });

        Route::group(['prefix' => 'tasks', 'as' => 'tasks.'], function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::post('/', [TaskController::class, 'store'])->name('store');
            Route::get('{id}', [TaskController::class, 'show'])->name('show');
            Route::put('{id}', [TaskController::class, 'update'])->name('update');
            Route::delete('{id}', [TaskController::class, 'destroy'])->name('destroy');
            Route::patch('{id}/status', [TaskController::class, 'updateStatus'])->name('update-status');
        });

        Route::get('task-statuses', [StatusController::class, 'index'])->name('task-statuses.index');
        Route::get('task-priorities', [PriorityController::class, 'index'])->name('task-priorities.index');

        Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
            Route::get('roles', [RoleController::class, 'index'])->middleware('auth.api:admin.users.list')->name('roles.index');
            Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('auth.api:admin.audit-logs.list')->name('audit-logs.index');
            Route::get('permissions', [PermissionController::class, 'index'])->middleware('auth.api:admin.roles.manage')->name('permissions.index');

            Route::group(['prefix' => 'roles', 'as' => 'roles.', 'middleware' => 'auth.api:admin.roles.manage'], function () {
                Route::get('{id}', [RoleController::class, 'show'])->name('show');
                Route::post('/', [RoleController::class, 'store'])->name('store');
                Route::put('{id}/permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions');
            });

            Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
                Route::get('/', [AdminUserController::class, 'index'])->middleware('auth.api:admin.users.list')->name('index');
                Route::post('/', [AdminUserController::class, 'store'])->middleware('auth.api:admin.users.create')->name('store');
                Route::get('{id}', [AdminUserController::class, 'show'])->middleware('auth.api:admin.users.show')->name('show');
                Route::put('{id}', [AdminUserController::class, 'update'])->middleware('auth.api:admin.users.update')->name('update');
                Route::post('{id}/ban', [AdminUserController::class, 'ban'])->middleware('auth.api:admin.users.ban')->name('ban');
                Route::post('{id}/activate', [AdminUserController::class, 'activate'])->middleware('auth.api:admin.users.activate')->name('activate');
                Route::patch('{id}/role', [AdminUserController::class, 'changeRole'])->middleware('auth.api:admin.users.role')->name('change-role');
            });
        });
    });
});
