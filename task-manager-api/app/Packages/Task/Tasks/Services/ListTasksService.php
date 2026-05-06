<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Task\Tasks\Repositories\TaskRepository;
use App\Packages\Task\Tasks\Resources\TaskResource;
use Illuminate\Pagination\LengthAwarePaginator;

class ListTasksService
{
    use CacheTrait;

    public function __construct(
        private TaskRepository $repository
    ) {}

    public function execute(string $userId, array $filters = []): LengthAwarePaginator
    {
        // Gerar chave de cache baseada nos filtros
        $cacheKey = 'tasks_user_' . $userId . '_' . md5(serialize($filters) . request()->get('page', 1));

        $paginator = $this->cache(
            key: $cacheKey,
            callback: function () use ($userId, $filters) {
                return $this->repository->listWithFilters($userId, $filters);
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
