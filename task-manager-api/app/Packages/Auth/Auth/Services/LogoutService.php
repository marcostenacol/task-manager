<?php

namespace App\Packages\Auth\Auth\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Auth\Auth\Repositories\AuthRepository;

class LogoutService
{
    use CacheTrait;

    public function execute(string $token): void
    {
        app(AuthRepository::class)->logout($token);

        // Limpa o cache do usuário e do token
        $userData = userObject();
        if ($userData) {
            $this->clearUserCache($userData->id);
        }

        $this->clearCache('token_', $token);
    }
}
