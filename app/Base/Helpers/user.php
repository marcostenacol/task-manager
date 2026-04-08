<?php

use App\Packages\Admin\User\Services\UserDataInCacheByTokenService;

/**
 * @return string
 */
function getClientIp(): string {
    return request()->header('X-Client-Ip') ?? request()->getClientIp();
}

function userObject(): mixed {
    return data_get(app(UserDataInCacheByTokenService::class)->execute(), 'user');
}

/**
 * @param string $permission
 * @return bool
 */
function hasPermission(string $permission): bool {
    return in_array($permission, userObject()->permissions ?? []);
}

/**
 * @return array
 */
function userOrganizations(): array {
    $organizations = [userObject()->organization->id];
    if (hasPermission('can_view_descendants_organizations') && userObject()->descendant_organizations) {
        $organizations = [...$organizations, ...userObject()->descendant_organizations];
    }
    if (hasPermission('can_view_sisters_organizations') && userObject()->sister_organizations) {
        $organizations = [...$organizations, ...userObject()->sister_organizations];
    }
    return array_unique($organizations);
}
