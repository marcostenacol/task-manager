<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Task\Statuses\Models\TaskStatus;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Support\Facades\DB;

class UpdateTaskStatusService
{
    use CacheTrait;

    public function __construct(
        private GuardTaskAccessService $guard_task_access_service,
        private RecordAuditLogService $record_audit_log_service,
    ) {}

    public function execute(string $task_id, string $status_id): TaskResource
    {
        $actor_id = userObject()->id;

        return DB::transaction(function () use ($task_id, $status_id, $actor_id) {
            $task = Task::findOrFail($task_id);
            $status = TaskStatus::findOrFail($status_id);

            $this->guard_task_access_service->guardCanAccess($task, $actor_id);

            $update_data = ['status_id' => $status_id];

            // Se o status for 'done', marca data de conclusão
            if ($status->slug === 'done') {
                $update_data['completed_at'] = now();
            } else {
                $update_data['completed_at'] = null;
            }

            $task->update($update_data);

            if ($task->visibility === 'organization') {
                $this->record_audit_log_service->execute($actor_id, 'task.status_update', 'Task', $task->id, [
                    'status_id' => $status_id,
                ], $task->organization_id);
            }

            // Invalida caches
            $this->clearCache('task_', $task_id);
            $this->clearCache('tasks_user_', $task->user_id.'*');

            return new TaskResource($task->load(['status', 'priority']));
        });
    }
}
