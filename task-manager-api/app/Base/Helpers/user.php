<?php

use App\Packages\Auth\Auth\Services\Cache\UserInCacheByTokenService;

/**
 * @return string
 */
function getClientIp(): string {
    return request()->header('X-Client-Ip') ?? request()->getClientIp();
}

function userObject(): mixed {
    return data_get(app(UserInCacheByTokenService::class)->execute(), 'user');
}

/**
 * @param string $permission
 * @return bool
 */
function hasPermission(string $permission): bool {
    return in_array($permission, userObject()->permissions ?? []);
}

