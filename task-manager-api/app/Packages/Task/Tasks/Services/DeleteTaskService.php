<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Base\Traits\Response;
use App\Packages\Task\Tasks\Models\Task;
use Illuminate\Support\Facades\DB;

class DeleteTaskService
{
    use CacheTrait, Response;

    public function execute(string $taskId): void
    {
        $authenticatedUserId = userObject()->id;

        DB::transaction(function () use ($taskId, $authenticatedUserId) {
            $task = Task::findOrFail($taskId);
            $userId = $task->user_id;

            if ($task->user_id !== $authenticatedUserId) {
                self::notAuthorizeExceptionResponse(
                    message: 'Recurso não encontrado.',
                    status_code: 404
                );
            }
            
            $task->delete();

            // Invalida caches
            $this->clearCache('task_', $taskId);
            $this->clearCache('tasks_user_', $userId . '*');
        });
    }
}
