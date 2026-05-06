<?php

namespace App\Packages\Admin\Users\Services;

use App\Packages\Admin\Users\Repositories\UserRepository;
use App\Packages\Admin\Users\Resources\AdminUserResource;
use App\Base\Traits\CacheTrait;

class DetailUserService
{
    use CacheTrait;

    public function __construct(
        private readonly UserRepository $repository
    ) {}

    public function execute(string $id): AdminUserResource
    {
        $user = $this->cache(
            "admin_user_detail_{$id}",
            fn () => $this->repository->findOrFail($id),
            config('api.cache.ttl', 86400)
        );

        if (!$user) {
            throw new \Exception('Usuário não encontrado.', 404);
        }

        return new AdminUserResource($user);
    }
}
