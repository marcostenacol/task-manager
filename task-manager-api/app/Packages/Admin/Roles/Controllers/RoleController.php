<?php

namespace App\Packages\Admin\Roles\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Admin\Roles\Requests\CreateRoleRequest;
use App\Packages\Admin\Roles\Requests\SyncRolePermissionsRequest;
use App\Packages\Admin\Roles\Requests\UpdateRoleLevelRequest;
use App\Packages\Admin\Roles\Requests\UpdateRoleNameRequest;
use App\Packages\Admin\Roles\Resources\RoleDetailResource;
use App\Packages\Admin\Roles\Resources\RoleResource;
use App\Packages\Admin\Roles\Services\CreateRoleService;
use App\Packages\Admin\Roles\Services\DeleteRoleService;
use App\Packages\Admin\Roles\Services\DetailRoleService;
use App\Packages\Admin\Roles\Services\ListRolesService;
use App\Packages\Admin\Roles\Services\SyncRolePermissionsService;
use App\Packages\Admin\Roles\Services\UpdateRoleLevelService;
use App\Packages\Admin\Roles\Services\UpdateRoleNameService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RoleController extends Controller
{
    use Response;

    public function __construct(
        private ListRolesService $listRolesService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $data = $this->listRolesService->execute(userObject()->id);

            return self::successResponse(RoleResource::collection($data), 'Roles recuperadas com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function show(string $id, DetailRoleService $service): JsonResponse
    {
        try {
            $data = $service->execute($id, userObject()->id);

            return self::successResponse(RoleDetailResource::make($data), 'Role recuperada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function store(CreateRoleRequest $request, CreateRoleService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute(
                $request->validated('name'),
                $admin->id,
                $request->validated('color'),
                $request->validated('scope')
            );

            return self::successResponse(
                RoleResource::make($data->loadCount('permissions')),
                'Role criada com sucesso.',
                HttpResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function updateName(string $id, UpdateRoleNameRequest $request, UpdateRoleNameService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($id, $request->validated('name'), $admin->id);

            return self::successResponse(RoleResource::make($data->loadCount('permissions')), 'Nome da role atualizado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function updateLevel(string $id, UpdateRoleLevelRequest $request, UpdateRoleLevelService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute(
                $id,
                (int) $request->validated('level'),
                $admin->id,
                $request->validated('color')
            );

            return self::successResponse(RoleResource::make($data->loadCount('permissions')), 'Role atualizada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function destroy(string $id, DeleteRoleService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute($id, $admin->id);

            return self::successResponse(null, 'Role excluída com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function syncPermissions(string $id, SyncRolePermissionsRequest $request, SyncRolePermissionsService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($id, $request->validated('permission_ids'), $admin->id);

            return self::successResponse(RoleDetailResource::make($data), 'Permissões da role atualizadas com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
