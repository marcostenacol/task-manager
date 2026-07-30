<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Organizations\Models\Organization;
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
        $new_visibility = $data['visibility'] ?? $task->visibility;
        $is_moving_between_organizations = $new_visibility === 'organization' && array_key_exists('organization_id', $data);

        if ($new_visibility === $task->visibility && ! $is_moving_between_organizations) {
            unset($data['visibility'], $data['organization_id']);

            return $data;
        }

        throw_unless($this->actorCanChangeVisibility($task, $actor_id), new \InvalidArgumentException('Você não tem permissão para mudar o escopo dessa task.'));

        $data['visibility'] = $new_visibility;
        $data['organization_id'] = $this->resolveOrganizationId($task, $new_visibility, $actor_id, $data['organization_id'] ?? null);

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

    /**
     * Ator global pode escolher explicitamente pra qual organization a task
     * vai (útil quando ele não tem organization ativa própria, ou quando quer
     * mover a task pra uma organization diferente da do dono). Qualquer outro
     * ator (dono ou Org Admin) sempre usa a organization ativa do dono da
     * task — nunca escolhe livremente, pra não vazar a task pra organization
     * errada.
     */
    private function resolveOrganizationId(Task $task, string $visibility, string $actor_id, ?string $requested_organization_id): ?string
    {
        if ($visibility !== 'organization') {
            return null;
        }

        if ($requested_organization_id && hasPermission('admin.organizations.list')) {
            throw_unless(Organization::find($requested_organization_id), new \InvalidArgumentException('Organization não encontrada.'));

            return $requested_organization_id;
        }

        $owner = User::findOrFail($task->user_id);

        throw_unless($owner->active_organization_id, new \InvalidArgumentException('Você não pertence a nenhuma organization para tornar essa task de organization.'));

        return $owner->active_organization_id;
    }
}
