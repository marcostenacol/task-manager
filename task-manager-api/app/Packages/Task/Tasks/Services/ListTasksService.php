<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Tasks\Repositories\TaskRepository;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Pagination\LengthAwarePaginator;

class ListTasksService
{
    use CacheTrait;

    public function __construct(
        private TaskRepository $repository
    ) {}

    public function execute(string $user_id, array $filters = []): LengthAwarePaginator
    {
        $actor = User::with('role')->findOrFail($user_id);
        $actor_is_global = $actor->global_role_id !== null || $actor->role->scope === 'global';

        // Gerar chave de cache baseada nos filtros
        $cache_key = 'tasks_user_'.$user_id.'_'.md5(serialize($filters).request()->get('page', 1));

        $paginator = $this->cache(
            key: $cache_key,
            callback: function () use ($user_id, $filters, $actor, $actor_is_global) {
                return $this->repository->listWithFilters($user_id, $filters, $actor->active_organization_id, $actor_is_global);
            },
            ttl: 300 // 5 minutos
        );

        // Transformar itens usando Resource
        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($item) => new TaskResource($item))
        );

        return $paginator;
    }
}
