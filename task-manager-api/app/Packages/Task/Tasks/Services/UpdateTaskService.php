<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Support\Facades\DB;

class UpdateTaskService
{
    use CacheTrait;

    public function __construct(
        private GuardTaskAccessService $guard_task_access_service,
        private RecordAuditLogService $record_audit_log_service,
    ) {}

    public function execute(string $task_id, array $data): TaskResource
    {
        $actor_id = userObject()->id;

        return DB::transaction(function () use ($task_id, $data, $actor_id) {
            $task = Task::findOrFail($task_id);

            $this->guard_task_access_service->guardCanAccess($task, $actor_id);

            $task->update($data);

            if ($task->visibility === 'organization') {
                $this->record_audit_log_service->execute($actor_id, 'task.update', 'Task', $task->id, [
                    'title' => $task->title,
                ], $task->organization_id);
            }

            // Invalida caches
            $this->clearCache('task_', $task_id);
            $this->clearCache('tasks_user_', $task->user_id.'*');

            return new TaskResource($task->load(['status', 'priority']));
        });
    }
}
