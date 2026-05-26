<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Base\Traits\Response;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;

class DetailTaskService
{
    use CacheTrait, Response;

    public function execute(string $taskId): TaskResource
    {
        $authenticatedUserId = userObject()->id;

        $task = $this->cache(
            key: 'task_' . $taskId,
            callback: function () use ($taskId) {
                return Task::with(['status', 'priority'])->findOrFail($taskId);
            },
            ttl: 600 // 10 minutos
        );

        if ($task->user_id !== $authenticatedUserId) {
            self::notAuthorizeExceptionResponse(
                message: 'Recurso não encontrado.',
                status_code: 404
            );
        }

        return new TaskResource($task);
    }
}
