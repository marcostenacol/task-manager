<?php

namespace App\Packages\Admin\Organizations\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Admin\Organizations\Requests\OnboardOrganizationRequest;
use App\Packages\Admin\Organizations\Resources\OrganizationResource;
use App\Packages\Admin\Organizations\Services\OnboardOrganizationService;
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
}
