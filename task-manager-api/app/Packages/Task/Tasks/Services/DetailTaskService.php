<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;

class DetailTaskService
{
    use CacheTrait;

    public function __construct(
        private GuardTaskAccessService $guard_task_access_service,
    ) {}

    public function execute(string $task_id): TaskResource
    {
        $actor_id = userObject()->id;

        $task = $this->cache(
            key: 'task_'.$task_id,
            callback: function () use ($task_id) {
                return Task::with(['status', 'priority'])->findOrFail($task_id);
            },
            ttl: 600 // 10 minutos
        );

        $this->guard_task_access_service->guardCanAccess($task, $actor_id);

        return new TaskResource($task);
    }
}
