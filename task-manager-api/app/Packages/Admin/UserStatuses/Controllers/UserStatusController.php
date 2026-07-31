<?php

namespace App\Packages\Admin\UserStatuses\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Admin\UserStatuses\Services\ListUserStatusesService;
use Illuminate\Http\JsonResponse;

class UserStatusController extends Controller
{
    use Response;

    public function __construct(
        private ListUserStatusesService $listUserStatusesService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $data = $this->listUserStatusesService->execute();

            return self::successResponse($data, 'Status de usuário recuperados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
