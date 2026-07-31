<?php

use App\Packages\Auth\Auth\Services\Cache\UserInCacheByTokenService;

function getClientIp(): string
{
    return request()->header('X-Client-Ip') ?? request()->getClientIp();
}

function userObject(): mixed
{
    return data_get(entityObject(), 'user');
}

function entityObject(): mixed
{
    return app(UserInCacheByTokenService::class)->execute();
}

function hasPermission(string $permission): bool
{
    return in_array($permission, userObject()->permissions ?? []);
}
