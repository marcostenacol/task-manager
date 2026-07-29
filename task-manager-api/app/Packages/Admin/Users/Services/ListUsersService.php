<?php

namespace App\Packages\Admin\Users\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Admin\Organizations\Services\ResolveOrganizationScopeService;
use App\Packages\Admin\Users\Repositories\UserRepository;
use App\Packages\Admin\Users\Resources\AdminUserResource;

class ListUsersService
{
    use CacheTrait;

    public function __construct(
        private readonly UserRepository $repository,
        private readonly ResolveOrganizationScopeService $resolveOrganizationScopeService,
    ) {}

    public function execute(array $filters, string $actorId): mixed
    {
        $organizationIds = $this->resolveOrganizationScopeService->execute($actorId);

        return $this->cache(
            'admin_users_list_'.md5(json_encode($filters + ['_scope' => $organizationIds])),
            function () use ($filters, $organizationIds) {
                $paginator = $this->repository->listWithFilters($filters, $organizationIds);

                $paginator->setCollection(
                    $paginator->getCollection()->map(fn ($item) => new AdminUserResource($item))
                );

                return $paginator;
            },
            config('api.cache.ttl', 86400)
        );
    }
}
