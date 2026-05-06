<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Task\Tasks\Models\Task;
use Illuminate\Support\Facades\DB;

class DeleteTaskService
{
    use CacheTrait;

    public function execute(string $taskId): void
    {
        DB::transaction(function () use ($taskId) {
            $task = Task::findOrFail($taskId);
            $userId = $task->user_id;
            
            $task->delete();

            // Invalida caches
            $this->clearCache('task_', $taskId);
            $this->clearCache('tasks_user_', $userId . '*');
        });
    }
}
