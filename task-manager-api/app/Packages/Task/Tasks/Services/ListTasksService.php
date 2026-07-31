<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Organizations\Services\ResolveOrganizationScopeService;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Tasks\Repositories\TaskRepository;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Pagination\LengthAwarePaginator;

class ListTasksService
{
    use CacheTrait;

    public function __construct(
        private TaskRepository $repository,
        private ResolveOrganizationScopeService $resolveOrganizationScopeService,
    ) {}

    public function execute(string $user_id, array $filters = []): LengthAwarePaginator
    {
        $actor = User::with('role')->findOrFail($user_id);
        $actor_is_global = $actor->global_role_id !== null || $actor->role->scope === 'global';
        $organization_ids = $this->resolveOrganizationScopeService->execute($user_id);

        // Chave de cache inclui a versão do grupo (ver CacheTrait::bumpCacheVersion) —
        // é assim que create/update/delete/assign invalidam todas as combinações de
        // filtro/página cacheadas de uma vez, sem depender de wildcard no forget.
        $cache_key = 'tasks_user_'.$user_id.'_v'.$this->cacheVersion('tasks_user_'.$user_id).'_'.md5(serialize($filters).request()->get('page', 1));

        $paginator = $this->cache(
            key: $cache_key,
            callback: function () use ($user_id, $filters, $organization_ids, $actor_is_global) {
                return $this->repository->listWithFilters($user_id, $filters, $organization_ids, $actor_is_global);
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
