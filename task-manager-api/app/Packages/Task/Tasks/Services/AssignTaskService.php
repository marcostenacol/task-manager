<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Organizations\Services\ResolveOrganizationScopeService;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Support\Facades\DB;

class AssignTaskService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $record_audit_log_service,
        private ResolveOrganizationScopeService $resolve_organization_scope_service,
    ) {}

    public function execute(string $task_id, string $new_user_id, string $actor_id): TaskResource
    {
        return DB::transaction(function () use ($task_id, $new_user_id, $actor_id) {
            $task = Task::findOrFail($task_id);

            throw_unless($task->visibility === 'organization', new \InvalidArgumentException('Só é possível atribuir tasks de organization — tasks pessoais não podem ser reatribuídas.'));

            $this->guardActorCanAssign($task, $actor_id);
            $this->guardNewOwnerBelongsToOrganization($task, $new_user_id);

            $old_user_id = $task->user_id;

            $task->update(['user_id' => $new_user_id]);

            $this->record_audit_log_service->execute($actor_id, 'task.assign', 'Task', $task->id, [
                'old_user_id' => $old_user_id,
                'new_user_id' => $new_user_id,
            ], $task->organization_id);

            $this->clearCache('task_', $task_id);
            $this->bumpCacheVersion('tasks_user_'.$old_user_id);
            $this->bumpCacheVersion('tasks_user_'.$new_user_id);

            return new TaskResource($task->load(['status', 'priority']));
        });
    }

    /**
     * Dono atual sempre pode delegar a task. Fora isso, mesma regra de
     * `UpdateTaskService::actorCanChangeVisibility()`: ator global administra
     * qualquer organization; Org Admin só a própria organization ativa.
     */
    private function guardActorCanAssign(Task $task, string $actor_id): void
    {
        if ($task->user_id === $actor_id) {
            return;
        }

        if (hasPermission('admin.organizations.list')) {
            return;
        }

        throw_unless(hasPermission('admin.organizations.manage-members'), new \InvalidArgumentException('Você não tem permissão para atribuir essa task.'));

        $organization_ids = $this->resolve_organization_scope_service->execute($actor_id);

        throw_unless(in_array($task->organization_id, $organization_ids ?? [], true), new \InvalidArgumentException('Você não tem permissão para atribuir essa task.'));
    }

    private function guardNewOwnerBelongsToOrganization(Task $task, string $new_user_id): void
    {
        $is_member = UserOrganization::where('user_id', $new_user_id)
            ->where('organization_id', $task->organization_id)
            ->exists();

        throw_unless($is_member, new \InvalidArgumentException('O novo responsável precisa ser membro dessa organization.'));
    }
}
