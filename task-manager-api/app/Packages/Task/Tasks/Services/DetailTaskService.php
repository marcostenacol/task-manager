<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;

class DetailTaskService
{
    use CacheTrait;

    public function execute(string $taskId): TaskResource
    {
        $task = $this->cache(
            key: 'task_' . $taskId,
            callback: function () use ($taskId) {
                return Task::with(['status', 'priority'])->findOrFail($taskId);
            },
            ttl: 600 // 10 minutos
        );

        return new TaskResource($task);
    }
}
