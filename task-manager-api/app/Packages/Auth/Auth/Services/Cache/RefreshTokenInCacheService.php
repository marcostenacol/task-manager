<?php

namespace App\Packages\Auth\Auth\Services\Cache;

use App\Base\Traits\CacheTrait;
use App\Packages\Auth\Auth\Enum\SettingsEnum;
use Illuminate\Support\Facades\DB;

class RefreshTokenInCacheService
{
    use CacheTrait;

    public function execute($refresh_token, $data = null)
    {
        if (! $refresh_token) {
            return null;
        }

        return $this->cache(
            key: 'refresh_token_'.$refresh_token,
            callback: function () use ($data, $refresh_token) {
                return $data ?:
                    json_decode(
                        DB::selectOne(
                            query: "SELECT
                                        json_build_object(
                                        'id', RT.id,
                                        'token', RT.token::TEXT,
                                        'created_at', RT.created_at,
                                        'user_id', RT.user_id,
                                        'is_expired', (RT.expires_at < NOW())
                                        ) AS data
                                    FROM admin.refresh_tokens RT
                                    WHERE token = ?;",
                            bindings: [handlerRequestToken($refresh_token)]
                        )?->data
                    );
            },
            ttl: (int) (SettingsEnum::getValue(SettingsEnum::REFRESH_TOKEN_EXPIRATION_HOURS->value ?? 'refresh_token.expiration_hours') * 3600)
        );
    }
}
