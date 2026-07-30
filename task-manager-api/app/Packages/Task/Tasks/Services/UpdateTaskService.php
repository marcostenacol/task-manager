<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Users\Models\User;
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

            $data = $this->resolveVisibilityChange($task, $data, $actor_id);

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

    private function resolveVisibilityChange(Task $task, array $data, string $actor_id): array
    {
        if (! array_key_exists('visibility', $data) || $data['visibility'] === $task->visibility) {
            unset($data['visibility']);

            return $data;
        }

        throw_unless($this->actorCanChangeVisibility($task, $actor_id), new \InvalidArgumentException('Você não tem permissão para mudar o escopo dessa task.'));

        $data['organization_id'] = $this->resolveOrganizationId($task, $data['visibility']);

        return $data;
    }

    /**
     * Dono sempre pode. Fora isso, é sempre mediante permissão: um ator global
     * (admin.organizations.list) administra tasks de organization de qualquer
     * organization; um Org Admin (admin.organizations.manage-members) só
     * administra tasks da própria organization ativa — nunca de outra.
     */
    private function actorCanChangeVisibility(Task $task, string $actor_id): bool
    {
        if ($task->user_id === $actor_id) {
            return true;
        }

        if (hasPermission('admin.organizations.list')) {
            return true;
        }

        if (! hasPermission('admin.organizations.manage-members')) {
            return false;
        }

        $actor = User::findOrFail($actor_id);

        return $task->organization_id !== null && $actor->active_organization_id === $task->organization_id;
    }

    private function resolveOrganizationId(Task $task, string $visibility): ?string
    {
        if ($visibility !== 'organization') {
            return null;
        }

        $owner = User::findOrFail($task->user_id);

        throw_unless($owner->active_organization_id, new \InvalidArgumentException('Você não pertence a nenhuma organization para tornar essa task de organization.'));

        return $owner->active_organization_id;
    }
}
