<?php

namespace App\Packages\Task\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Base\Traits\Response;
use App\Packages\Task\Tasks\Services\ListTasksService;
use App\Packages\Task\Tasks\Services\CreateTaskService;
use App\Packages\Task\Tasks\Services\DetailTaskService;
use App\Packages\Task\Tasks\Services\UpdateTaskService;
use App\Packages\Task\Tasks\Services\DeleteTaskService;
use App\Packages\Task\Tasks\Services\UpdateTaskStatusService;
use App\Packages\Task\Tasks\Requests\CreateTaskRequest;
use App\Packages\Task\Tasks\Requests\UpdateTaskRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    use Response;

    public function index(Request $request, ListTasksService $service): JsonResponse
    {
        try {
            $user = userObject();
            $data = $service->execute($user->id, $request->all());
            return self::successResponse($data, 'Tarefas listadas com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function store(CreateTaskRequest $request, CreateTaskService $service): JsonResponse
    {
        try {
            $user = userObject();
            $data = $service->execute($user->id, $request->validated());
            return self::successResponse($data, 'Tarefa criada com sucesso.', 201);
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function show(string $id, DetailTaskService $service): JsonResponse
    {
        try {
            $data = $service->execute($id);
            return self::successResponse($data, 'Tarefa recuperada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function update(string $id, UpdateTaskRequest $request, UpdateTaskService $service): JsonResponse
    {
        try {
            $data = $service->execute($id, $request->validated());
            return self::successResponse($data, 'Tarefa atualizada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function destroy(string $id, DeleteTaskService $service): JsonResponse
    {
        try {
            $service->execute($id);
            return self::successResponse(null, 'Tarefa excluída com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function updateStatus(string $id, Request $request, UpdateTaskStatusService $service): JsonResponse
    {
        try {
            $request->validate(['status_id' => 'required|uuid']);
            $data = $service->execute($id, $request->status_id);
            return self::successResponse($data, 'Status da tarefa atualizado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
