<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;
use App\Packages\Task\Statuses\Models\TaskStatus;
use Illuminate\Support\Facades\DB;

class UpdateTaskStatusService
{
    use CacheTrait;

    public function execute(string $taskId, string $statusId): TaskResource
    {
        return DB::transaction(function () use ($taskId, $statusId) {
            $task = Task::findOrFail($taskId);
            $status = TaskStatus::findOrFail($statusId);

            $updateData = ['status_id' => $statusId];

            // Se o status for 'done', marca data de conclusão
            if ($status->slug === 'done') {
                $updateData['completed_at'] = now();
            } else {
                $updateData['completed_at'] = null;
            }

            $task->update($updateData);

            // Invalida caches
            $this->clearCache('task_', $taskId);
            $this->clearCache('tasks_user_', $task->user_id . '*');

            return new TaskResource($task->load(['status', 'priority']));
        });
    }
}
