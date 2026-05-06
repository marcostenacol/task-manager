<?php

namespace App\Packages\Task\Statuses\Controllers;

use App\Http\Controllers\Controller;
use App\Base\Traits\Response;
use App\Packages\Task\Statuses\Models\TaskStatus;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    use Response;

    public function index(): JsonResponse
    {
        try {
            $data = TaskStatus::orderBy('slug')->get();
            return self::successResponse($data, 'Status recuperados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
