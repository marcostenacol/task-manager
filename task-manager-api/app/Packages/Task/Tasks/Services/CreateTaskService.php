<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Task\Tasks\Models\Task;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Support\Facades\DB;

class CreateTaskService
{
    use CacheTrait;

    public function execute(string $userId, array $data): TaskResource
    {
        return DB::transaction(function () use ($userId, $data) {
            $task = Task::create([
                'user_id' => $userId,
                'status_id' => $data['status_id'],
                'priority_id' => $data['priority_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'] ?? null,
            ]);

            // Invalida cache de listagem do usuário
            // Como as chaves de listagem contêm hash de filtros, podemos limpar por prefixo se o driver suportar
            // Ou apenas confiar no TTL curto. Aqui vamos limpar a chave base de perfil se houver.
            $this->clearCache('tasks_user_', $userId . '*');

            return new TaskResource($task->load(['status', 'priority']));
        });
    }
}
