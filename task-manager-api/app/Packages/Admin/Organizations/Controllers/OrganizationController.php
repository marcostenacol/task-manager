<?php

namespace App\Packages\Admin\Organizations\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Admin\Organizations\Requests\AddOrganizationMemberRequest;
use App\Packages\Admin\Organizations\Requests\CreateOrganizationMemberRequest;
use App\Packages\Admin\Organizations\Requests\CreateOrganizationRequest;
use App\Packages\Admin\Organizations\Requests\LookupOrganizationMemberRequest;
use App\Packages\Admin\Organizations\Requests\OnboardOrganizationRequest;
use App\Packages\Admin\Organizations\Requests\RemoveOrganizationMemberRequest;
use App\Packages\Admin\Organizations\Requests\SwitchActiveOrganizationRequest;
use App\Packages\Admin\Organizations\Requests\TransferOrganizationOwnershipRequest;
use App\Packages\Admin\Organizations\Requests\UpdateOrganizationMemberRoleRequest;
use App\Packages\Admin\Organizations\Requests\UpdateOrganizationRequest;
use App\Packages\Admin\Organizations\Resources\MyOrganizationMembershipResource;
use App\Packages\Admin\Organizations\Resources\OrganizationMemberLookupResource;
use App\Packages\Admin\Organizations\Resources\OrganizationMemberResource;
use App\Packages\Admin\Organizations\Resources\OrganizationResource;
use App\Packages\Admin\Organizations\Services\AddOrganizationMemberService;
use App\Packages\Admin\Organizations\Services\CreateOrganizationMemberService;
use App\Packages\Admin\Organizations\Services\CreateOrganizationService;
use App\Packages\Admin\Organizations\Services\ListMyOrganizationsService;
use App\Packages\Admin\Organizations\Services\ListOrganizationMembersService;
use App\Packages\Admin\Organizations\Services\ListOrganizationsService;
use App\Packages\Admin\Organizations\Services\LookupOrganizationMemberService;
use App\Packages\Admin\Organizations\Services\OnboardOrganizationService;
use App\Packages\Admin\Organizations\Services\RemoveOrganizationMemberService;
use App\Packages\Admin\Organizations\Services\SwitchActiveOrganizationService;
use App\Packages\Admin\Organizations\Services\TransferOrganizationOwnershipService;
use App\Packages\Admin\Organizations\Services\UpdateOrganizationMemberRoleService;
use App\Packages\Admin\Organizations\Services\UpdateOrganizationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class OrganizationController extends Controller
{
    use Response;

    public function onboard(OnboardOrganizationRequest $request, OnboardOrganizationService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($request->validated('name'), $admin->id);

            return self::successResponse(
                OrganizationResource::make($data),
                'Organization criada com sucesso.',
                HttpResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function lookupMember(LookupOrganizationMemberRequest $request, LookupOrganizationMemberService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute(
                $request->validated('cpf'),
                $admin->id,
                $request->validated('organization_id')
            );

            return self::successResponse(
                $data ? OrganizationMemberLookupResource::make($data) : null,
                $data ? 'Usuário encontrado.' : 'Nenhum usuário encontrado com esse CPF.',
                HttpResponse::HTTP_OK,
                true
            );
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function addMember(AddOrganizationMemberRequest $request, AddOrganizationMemberService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute(
                $request->validated('user_id'),
                $request->validated('role_id'),
                $admin->id,
                $request->validated('organization_id')
            );

            return self::successResponse(null, 'Membro adicionado com sucesso.', HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function createMember(CreateOrganizationMemberRequest $request, CreateOrganizationMemberService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute($request->validated(), $admin->id);

            return self::successResponse(null, 'Usuário criado e adicionado à organization com sucesso.', HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function updateMemberRole(string $user_id, UpdateOrganizationMemberRoleRequest $request, UpdateOrganizationMemberRoleService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute(
                $user_id,
                $request->validated('role_id'),
                $admin->id,
                $request->validated('organization_id')
            );

            return self::successResponse(null, 'Role do membro atualizada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function removeMember(string $user_id, RemoveOrganizationMemberRequest $request, RemoveOrganizationMemberService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute($user_id, $admin->id, $request->validated('organization_id'));

            return self::successResponse(null, 'Membro removido com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function index(ListOrganizationsService $service): JsonResponse
    {
        try {
            $data = $service->execute();

            return self::successResponse(OrganizationResource::collection($data), 'Organizations recuperadas com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function store(CreateOrganizationRequest $request, CreateOrganizationService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute(
                $request->validated('name'),
                $request->validated('parent_id'),
                $admin->id,
                $request->validated('owner_cpf')
            );

            return self::successResponse(
                OrganizationResource::make($data),
                'Organization criada com sucesso.',
                HttpResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function members(string $id, ListOrganizationMembersService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($id, $admin->id);

            return self::successResponse(OrganizationMemberResource::collection($data), 'Membros recuperados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function update(string $id, UpdateOrganizationRequest $request, UpdateOrganizationService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($id, $request->validated('name'), $admin->id);

            return self::successResponse(OrganizationResource::make($data), 'Organization atualizada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function mine(ListMyOrganizationsService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($admin->id);

            return self::successResponse(MyOrganizationMembershipResource::collection($data), 'Organizations recuperadas com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function switchActive(SwitchActiveOrganizationRequest $request, SwitchActiveOrganizationService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute($request->validated('organization_id'), $admin->id);

            return self::successResponse(null, 'Organization ativa alterada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function transferOwnership(TransferOrganizationOwnershipRequest $request, TransferOrganizationOwnershipService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $service->execute(
                $request->validated('new_owner_user_id'),
                $admin->id,
                $request->validated('organization_id')
            );

            return self::successResponse(null, 'Titularidade da organization transferida com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
