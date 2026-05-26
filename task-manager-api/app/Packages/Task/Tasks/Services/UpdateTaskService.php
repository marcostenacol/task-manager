<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Base\Traits\Response;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Support\Facades\DB;

class UpdateTaskService
{
    use CacheTrait, Response;

    public function execute(string $taskId, array $data): TaskResource
    {
        $authenticatedUserId = userObject()->id;

        return DB::transaction(function () use ($taskId, $data, $authenticatedUserId) {
            $task = Task::findOrFail($taskId);

            if ($task->user_id !== $authenticatedUserId) {
                self::notAuthorizeExceptionResponse(
                    message: 'Recurso não encontrado.',
                    status_code: 404
                );
            }
            $task->update($data);

            // Invalida caches
            $this->clearCache('task_', $taskId);
            $this->clearCache('tasks_user_', $task->user_id . '*');

            return new TaskResource($task->load(['status', 'priority']));
        });
    }
}
