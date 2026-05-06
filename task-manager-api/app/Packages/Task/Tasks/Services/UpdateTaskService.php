<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Support\Facades\DB;

class UpdateTaskService
{
    use CacheTrait;

    public function execute(string $taskId, array $data): TaskResource
    {
        return DB::transaction(function () use ($taskId, $data) {
            $task = Task::findOrFail($taskId);
            $task->update($data);

            // Invalida caches
            $this->clearCache('task_', $taskId);
            $this->clearCache('tasks_user_', $task->user_id . '*');

            return new TaskResource($task->load(['status', 'priority']));
        });
    }
}
