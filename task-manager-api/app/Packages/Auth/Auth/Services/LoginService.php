<?php

namespace App\Packages\Auth\Auth\Services;

use App\Base\Traits\CacheTrait;
use App\Packages\Auth\Auth\Services\Cache\RefreshTokenInCacheService;
use App\Packages\Auth\Auth\Services\Cache\TokenInCacheService;
use App\Packages\Auth\Auth\Services\Cache\UserInCacheByTokenService;
use Illuminate\Support\Facades\DB;

class LoginService
{
    use CacheTrait;

    public function execute(string $username, string $password): mixed
    {
        $response = json_decode(
            DB::selectOne(
                'select * from admin.process_login(?, ?);',
                [$username, $password]
            )->data
        );

        app(TokenInCacheService::class)->execute(
            token: $response->access_token->token,
            data: $response->access_token
        );

        app(RefreshTokenInCacheService::class)->execute(
            refresh_token: $response->access_token->token, // Usando o access_token como chave conforme padrão do trecho fornecido
            data: $response->refresh_token
        );

        $this->clearUserCache($response->user_data->user->id);

        return app(UserInCacheByTokenService::class)->execute(
            token: $response->access_token->token,
            user_data: $response->user_data
        );
    }
}
