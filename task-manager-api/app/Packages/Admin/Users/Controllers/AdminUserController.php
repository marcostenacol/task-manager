<?php

namespace App\Packages\Admin\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Base\Traits\Response;
use App\Packages\Admin\Users\Services\ListUsersService;
use App\Packages\Admin\Users\Services\DetailUserService;
use App\Packages\Admin\Users\Services\BanUserService;
use App\Packages\Admin\Users\Services\ActivateUserService;
use App\Packages\Admin\Users\Services\ChangeUserRoleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    use Response;

    public function index(Request $request, ListUsersService $service): JsonResponse
    {
        try {
            $data = $service->execute($request->all());
            return self::successResponse($data, 'Usuários listados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function show(string $id, DetailUserService $service): JsonResponse
    {
        try {
            $data = $service->execute($id);
            return self::successResponse($data, 'Usuário recuperado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function ban(string $id, Request $request, BanUserService $service): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:255'
            ]);

            $admin = userObject();
            $service->execute($id, $request->reason, $admin->id);

            return self::successResponse(null, 'Usuário banido com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function activate(string $id, ActivateUserService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute($id, $admin->id);

            return self::successResponse(null, 'Usuário ativado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function changeRole(string $id, Request $request, ChangeUserRoleService $service): JsonResponse
    {
        try {
            $request->validate([
                'role_id' => 'required|uuid|exists:App\Packages\Admin\Roles\Models\Role,id'
            ]);

            $service->execute($id, $request->role_id);

            return self::successResponse(null, 'Role do usuário alterada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
