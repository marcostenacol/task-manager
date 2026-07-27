<?php

namespace App\Packages\Task\Statuses\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Task\Statuses\Services\ListStatusesService;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    use Response;

    public function __construct(
        private ListStatusesService $listStatusesService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $data = $this->listStatusesService->execute();

            return self::successResponse($data, 'Status recuperados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
