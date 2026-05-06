<?php

namespace App\Packages\Task\Priorities\Controllers;

use App\Http\Controllers\Controller;
use App\Base\Traits\Response;
use App\Packages\Task\Priorities\Models\TaskPriority;
use Illuminate\Http\JsonResponse;

class PriorityController extends Controller
{
    use Response;

    public function index(): JsonResponse
    {
        try {
            $data = TaskPriority::orderBy('order')->get();
            return self::successResponse($data, 'Prioridades recuperadas com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
