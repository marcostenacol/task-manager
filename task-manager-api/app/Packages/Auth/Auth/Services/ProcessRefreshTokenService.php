<?php

namespace App\Packages\Auth\Auth\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Auth\Auth\Repositories\AuthRepository;
use App\Packages\Auth\Auth\Services\Cache\RefreshTokenInCacheService;
use App\Packages\Auth\Auth\Services\Cache\TokenInCacheService;
use App\Packages\Auth\Auth\Services\Cache\UserInCacheByTokenService;

class ProcessRefreshTokenService
{
    use CacheTrait;

    public function execute(string $refreshToken): mixed
    {
        $response = app(AuthRepository::class)->processRefresh($refreshToken);

        // Atualiza Cache com novos tokens
        app(TokenInCacheService::class)->execute(
            token: $response->access_token->token,
            data: $response->access_token
        );

        app(RefreshTokenInCacheService::class)->execute(
            refresh_token: $response->access_token->token,
            data: $response->refresh_token
        );

        $this->clearUserCache($response->user_data->user->id);

        return app(UserInCacheByTokenService::class)->execute(
            token: $response->access_token->token,
            user_data: $response->user_data
        );
    }
}
