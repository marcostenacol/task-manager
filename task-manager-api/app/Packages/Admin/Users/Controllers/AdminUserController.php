<?php

namespace App\Packages\Admin\Users\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Admin\Users\Requests\CreateUserRequest;
use App\Packages\Admin\Users\Requests\ResetUserPasswordRequest;
use App\Packages\Admin\Users\Requests\UpdateUserRequest;
use App\Packages\Admin\Users\Resources\AdminUserResource;
use App\Packages\Admin\Users\Services\ActivateUserService;
use App\Packages\Admin\Users\Services\BanUserService;
use App\Packages\Admin\Users\Services\ChangeUserRoleService;
use App\Packages\Admin\Users\Services\CreateUserService;
use App\Packages\Admin\Users\Services\DeleteUserService;
use App\Packages\Admin\Users\Services\DetailUserService;
use App\Packages\Admin\Users\Services\ListUsersService;
use App\Packages\Admin\Users\Services\ResetUserPasswordService;
use App\Packages\Admin\Users\Services\UpdateUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminUserController extends Controller
{
    use Response;

    public function index(Request $request, ListUsersService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($request->all(), $admin->id);

            return self::successResponse($data, 'Usuários listados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function store(CreateUserRequest $request, CreateUserService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($request->validated(), $admin->id);

            return self::successResponse(
                AdminUserResource::make($data),
                'Usuário criado com sucesso.',
                HttpResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function update(string $id, UpdateUserRequest $request, UpdateUserService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($id, $request->validated(), $admin->id);

            return self::successResponse(AdminUserResource::make($data), 'Usuário atualizado com sucesso.');
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
                'reason' => 'required|string|max:255',
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

    public function resetPassword(string $id, ResetUserPasswordRequest $request, ResetUserPasswordService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute($id, $request->validated('password'), $admin->id);

            return self::successResponse(null, 'Senha do usuário redefinida com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function destroy(string $id, DeleteUserService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute($id, $admin->id);

            return self::successResponse(null, 'Usuário excluído com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function changeRole(string $id, Request $request, ChangeUserRoleService $service): JsonResponse
    {
        try {
            $request->validate([
                'role_id' => 'required|uuid|exists:App\Packages\Admin\Roles\Models\Role,id',
            ]);

            $admin = userObject();
            $service->execute($id, $request->role_id, $admin->id);

            return self::successResponse(null, 'Role do usuário alterada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
