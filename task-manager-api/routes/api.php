<?php

use App\Packages\Admin\AuditLogs\Controllers\AuditLogController;
use App\Packages\Admin\Organizations\Controllers\OrganizationController;
use App\Packages\Admin\Permissions\Controllers\PermissionController;
use App\Packages\Admin\Roles\Controllers\RoleController;
use App\Packages\Admin\Settings\Controllers\SettingController;
use App\Packages\Admin\Users\Controllers\AdminUserController;
use App\Packages\Admin\UserStatuses\Controllers\UserStatusController;
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

        Route::group(['prefix' => 'organizations', 'as' => 'organizations.'], function () {
            Route::post('onboarding', [OrganizationController::class, 'onboard'])
                ->middleware('throttle:organization-onboarding')
                ->name('onboarding');

            Route::get('mine', [OrganizationController::class, 'mine'])->name('mine');
            Route::patch('active', [OrganizationController::class, 'switchActive'])->name('switch-active');

            Route::group(['middleware' => 'auth.api:admin.organizations.manage-members'], function () {
                Route::get('members/lookup', [OrganizationController::class, 'lookupMember'])
                    ->middleware('throttle:organization-member-lookup')
                    ->name('members.lookup');
                Route::post('members', [OrganizationController::class, 'addMember'])->name('members.add');
                Route::post('sub', [OrganizationController::class, 'createSubOrganization'])->name('sub.create');
                Route::post('members/create', [OrganizationController::class, 'createMember'])->name('members.create');
                Route::post('transfer-ownership', [OrganizationController::class, 'transferOwnership'])->name('transfer-ownership');
                Route::put('{id}', [OrganizationController::class, 'update'])->name('update');
                Route::get('{id}/members', [OrganizationController::class, 'members'])->name('members.show');
                Route::put('members/{user_id}/role', [OrganizationController::class, 'updateMemberRole'])->name('members.update-role');
                Route::delete('members/{user_id}', [OrganizationController::class, 'removeMember'])->name('members.remove');
            });
        });

        Route::group(['prefix' => 'social', 'as' => 'social.'], function () {
            Route::get('profile', [PersonController::class, 'show'])->name('profile.show');
            Route::put('profile', [PersonController::class, 'update'])->name('profile.update');
            Route::post('profile/avatar', [PersonController::class, 'avatar'])->name('profile.avatar');
            Route::put('profile/password', [PersonController::class, 'changePassword'])->name('profile.password');

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
            Route::patch('{id}/assign', [TaskController::class, 'assign'])->name('assign');
        });

        Route::get('task-statuses', [StatusController::class, 'index'])->name('task-statuses.index');
        Route::get('task-priorities', [PriorityController::class, 'index'])->name('task-priorities.index');

        Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
            Route::get('roles', [RoleController::class, 'index'])->middleware('auth.api:admin.users.list')->name('roles.index');
            Route::get('user-statuses', [UserStatusController::class, 'index'])->middleware('auth.api:admin.users.list')->name('user-statuses.index');
            Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('auth.api:admin.audit-logs.list')->name('audit-logs.index');
            Route::get('permissions', [PermissionController::class, 'index'])->middleware('auth.api:admin.roles.manage')->name('permissions.index');

            Route::group(['prefix' => 'roles', 'as' => 'roles.', 'middleware' => 'auth.api:admin.roles.manage'], function () {
                Route::get('{id}', [RoleController::class, 'show'])->name('show');
                Route::post('/', [RoleController::class, 'store'])->name('store');
                Route::put('{id}/permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions');
                Route::patch('{id}/name', [RoleController::class, 'updateName'])->name('update-name');
                Route::patch('{id}/level', [RoleController::class, 'updateLevel'])->name('update-level');
                Route::delete('{id}', [RoleController::class, 'destroy'])->name('destroy');
            });

            Route::group(['prefix' => 'settings', 'as' => 'settings.', 'middleware' => 'auth.api:admin.settings.manage'], function () {
                Route::get('/', [SettingController::class, 'index'])->name('index');
                Route::put('{id}', [SettingController::class, 'update'])->name('update');
            });

            Route::group(['prefix' => 'organizations', 'as' => 'organizations.', 'middleware' => 'auth.api:admin.organizations.list'], function () {
                Route::get('/', [OrganizationController::class, 'index'])->name('index');
                Route::post('/', [OrganizationController::class, 'store'])->name('store');
                Route::get('{id}/members', [OrganizationController::class, 'members'])->name('members');
            });

            Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
                Route::get('/', [AdminUserController::class, 'index'])->middleware('auth.api:admin.users.list')->name('index');
                Route::post('/', [AdminUserController::class, 'store'])->middleware('auth.api:admin.users.create')->name('store');
                Route::get('{id}', [AdminUserController::class, 'show'])->middleware('auth.api:admin.users.show')->name('show');
                Route::put('{id}', [AdminUserController::class, 'update'])->middleware('auth.api:admin.users.update')->name('update');
                Route::post('{id}/ban', [AdminUserController::class, 'ban'])->middleware('auth.api:admin.users.ban')->name('ban');
                Route::post('{id}/activate', [AdminUserController::class, 'activate'])->middleware('auth.api:admin.users.activate')->name('activate');
                Route::patch('{id}/role', [AdminUserController::class, 'changeRole'])->middleware('auth.api:admin.users.role')->name('change-role');
                Route::put('{id}/password', [AdminUserController::class, 'resetPassword'])->middleware('auth.api:admin.users.reset-password')->name('reset-password');
                Route::delete('{id}', [AdminUserController::class, 'destroy'])->middleware('auth.api:admin.users.delete')->name('destroy');
            });
        });
    });
});
