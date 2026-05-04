<?php

namespace App\Packages\Auth\Auth\Services\Cache;

use App\Base\Traits\CacheTrait;
use App\Packages\Auth\Auth\Enum\SettingsEnum;
use Illuminate\Support\Facades\DB;

class TokenInCacheService
{
    use CacheTrait;

    public function execute($token, $data = null)
    {
        if (! $token) {
            return null;
        }

        return $this->cache(
            key: 'token_'.$token,
            callback: function () use ($data, $token) {
                return $data ?:
                    json_decode(
                        DB::selectOne(
                            query: "SELECT
                                        json_build_object(
                                        'id', PT.id,
                                        'token', PT.token::TEXT,
                                        'created_at', PT.created_at,
                                        'user_id', PT.user_id,
                                        'is_expired', (PT.expires_at < NOW())
                                        ) AS data
                                    FROM admin.personal_access_tokens PT
                                    WHERE token = ?;",
                            bindings: [handlerRequestToken($token)]
                        )?->data
                    );
            },
            ttl: (int) (SettingsEnum::getValue(SettingsEnum::TOKEN_EXPIRATION_MINUTES->value ?? 'token_expiration_minutes') * 60)
        );
    }
}
