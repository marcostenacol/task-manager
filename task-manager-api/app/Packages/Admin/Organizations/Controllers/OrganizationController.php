<?php

namespace App\Packages\Admin\Organizations\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Admin\Organizations\Requests\AddOrganizationMemberRequest;
use App\Packages\Admin\Organizations\Requests\LookupOrganizationMemberRequest;
use App\Packages\Admin\Organizations\Requests\OnboardOrganizationRequest;
use App\Packages\Admin\Organizations\Requests\SwitchActiveOrganizationRequest;
use App\Packages\Admin\Organizations\Resources\MyOrganizationMembershipResource;
use App\Packages\Admin\Organizations\Resources\OrganizationMemberLookupResource;
use App\Packages\Admin\Organizations\Resources\OrganizationResource;
use App\Packages\Admin\Organizations\Services\AddOrganizationMemberService;
use App\Packages\Admin\Organizations\Services\ListMyOrganizationsService;
use App\Packages\Admin\Organizations\Services\LookupOrganizationMemberService;
use App\Packages\Admin\Organizations\Services\OnboardOrganizationService;
use App\Packages\Admin\Organizations\Services\SwitchActiveOrganizationService;
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
            $data = $service->execute($request->validated('cpf'), $admin->id);

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
            $service->execute($request->validated('user_id'), $request->validated('role_id'), $admin->id);

            return self::successResponse(null, 'Membro adicionado com sucesso.', HttpResponse::HTTP_CREATED);
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
}
