<?php

namespace App\Packages\Auth\Auth\Services\Cache;

use App\Base\Traits\CacheTrait;
use Illuminate\Support\Facades\DB;

class UserInCacheByTokenService
{
    use CacheTrait;

    public function execute(?string $token = null, mixed $user_data = null): mixed
    {
        $token = $token ?? $this->getToken();

        if (! $token) {
            return null;
        }

        $data = app(TokenInCacheService::class)->execute($token);

        if (! $data) {
            return null;
        }

        return $this->cache(
            key: 'user_id_'.$data->user_id,
            callback: function () use ($user_data, $token) {
                return $user_data ?: json_decode(DB::selectOne('select * from admin.get_user_by_token(?, ?);', [handlerRequestToken($token), false])->data);
            }
        );
    }

    private function getToken(): ?string
    {
        return handlerRequestToken(request()->bearerToken());
    }
}
