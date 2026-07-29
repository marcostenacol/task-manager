<?php

namespace App\Packages\Admin\Permissions\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Admin\Permissions\Resources\PermissionResource;
use App\Packages\Admin\Permissions\Services\ListPermissionsService;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    use Response;

    public function __construct(
        private ListPermissionsService $listPermissionsService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $data = $this->listPermissionsService->execute();

            return self::successResponse(PermissionResource::collection($data), 'Permissões recuperadas com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
