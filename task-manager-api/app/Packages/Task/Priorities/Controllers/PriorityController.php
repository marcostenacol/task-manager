<?php

namespace App\Packages\Task\Priorities\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Task\Priorities\Services\ListPrioritiesService;
use Illuminate\Http\JsonResponse;

class PriorityController extends Controller
{
    use Response;

    public function __construct(
        private ListPrioritiesService $listPrioritiesService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $data = $this->listPrioritiesService->execute();

            return self::successResponse($data, 'Prioridades recuperadas com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
