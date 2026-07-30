<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\AuditLogs\Services\RecordAuditLogService;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Support\Facades\DB;

class CreateTaskService
{
    use CacheTrait;

    public function __construct(
        private RecordAuditLogService $record_audit_log_service,
    ) {}

    public function execute(string $user_id, array $data): TaskResource
    {
        return DB::transaction(function () use ($user_id, $data) {
            $user = User::findOrFail($user_id);

            $visibility = $data['visibility'] ?? 'personal';

            $organization_id = $this->resolveOrganizationId($user, $visibility);

            $task = Task::create([
                'user_id' => $user_id,
                'organization_id' => $organization_id,
                'visibility' => $visibility,
                'status_id' => $data['status_id'],
                'priority_id' => $data['priority_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'] ?? null,
            ]);

            if ($visibility === 'organization') {
                $this->record_audit_log_service->execute($user_id, 'task.create', 'Task', $task->id, [
                    'title' => $task->title,
                ], $organization_id);
            }

            // Invalida cache de listagem do usuário
            // Como as chaves de listagem contêm hash de filtros, podemos limpar por prefixo se o driver suportar
            // Ou apenas confiar no TTL curto. Aqui vamos limpar a chave base de perfil se houver.
            $this->clearCache('tasks_user_', $user_id.'*');

            return new TaskResource($task->load(['status', 'priority']));
        });
    }

    private function resolveOrganizationId(User $user, string $visibility): ?string
    {
        if ($visibility !== 'organization') {
            return null;
        }

        throw_unless($user->active_organization_id, new \InvalidArgumentException('Você não pertence a nenhuma organization para criar uma task de organization.'));

        return $user->active_organization_id;
    }
}
