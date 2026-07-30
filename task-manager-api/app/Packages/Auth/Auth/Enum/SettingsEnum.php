<?php

namespace App\Packages\Auth\Auth\Enum;

enum SettingsEnum: string
{
    case TOKEN_EXPIRATION_MINUTES = 'token_expiration_minutes';
    case REFRESH_TOKEN_EXPIRATION_HOURS = 'refresh_token.expiration_hours';
    case ORGANIZATION_ONBOARDING_RATE_LIMIT_PER_HOUR = 'organization_onboarding_rate_limit_per_hour';
    case ORGANIZATION_MAX_ACTIVE_PER_FOUNDER = 'organization_max_active_per_founder';
    case ORGANIZATION_MEMBER_LOOKUP_RATE_LIMIT_PER_HOUR = 'organization_member_lookup_rate_limit_per_hour';

    public static function getValue(string|SettingsEnum $key): mixed
    {
        $settingKey = $key instanceof SettingsEnum ? $key->value : $key;

        return getSetting($settingKey);
    }
}
