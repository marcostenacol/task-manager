<?php

namespace App\Packages\Admin\Users\Services;

use App\Packages\Admin\Users\Repositories\UserRepository;
use App\Packages\Admin\Users\Resources\AdminUserResource;
use App\Base\Traits\CacheTrait;

class ListUsersService
{
    use CacheTrait;

    public function __construct(
        private readonly UserRepository $repository
    ) {}

    public function execute(array $filters): mixed
    {
        return $this->cache(
            'admin_users_list_' . md5(json_encode($filters)),
            function () use ($filters) {
                $paginator = $this->repository->listWithFilters($filters);
                
                $paginator->setCollection(
                    $paginator->getCollection()->map(fn ($item) => new AdminUserResource($item))
                );

                return $paginator;
            },
            config('api.cache.ttl', 86400)
        );
    }
}
