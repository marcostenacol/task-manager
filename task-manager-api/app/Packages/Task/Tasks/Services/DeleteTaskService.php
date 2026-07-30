<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Task\Tasks\Models\Task;
use Illuminate\Support\Facades\DB;

class DeleteTaskService
{
    use CacheTrait;

    public function __construct(
        private GuardTaskAccessService $guard_task_access_service,
        private RecordAuditLogService $record_audit_log_service,
    ) {}

    public function execute(string $task_id): void
    {
        $actor_id = userObject()->id;

        DB::transaction(function () use ($task_id, $actor_id) {
            $task = Task::findOrFail($task_id);
            $user_id = $task->user_id;

            $this->guard_task_access_service->guardCanAccess($task, $actor_id, require_owner: true);

            if ($task->visibility === 'organization') {
                $this->record_audit_log_service->execute($actor_id, 'task.delete', 'Task', $task->id, [
                    'title' => $task->title,
                ], $task->organization_id);
            }

            $task->delete();

            // Invalida caches
            $this->clearCache('task_', $task_id);
            $this->clearCache('tasks_user_', $user_id.'*');
        });
    }
}
