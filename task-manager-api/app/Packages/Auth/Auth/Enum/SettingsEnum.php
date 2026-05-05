<?php

namespace App\Packages\Auth\Auth\Enum;

enum SettingsEnum: string
{
    case TOKEN_EXPIRATION_MINUTES = 'token_expiration_minutes';
    case REFRESH_TOKEN_EXPIRATION_HOURS = 'refresh_token.expiration_hours';

    public static function getValue(string|SettingsEnum $key): mixed
    {
        $settingKey = $key instanceof SettingsEnum ? $key->value : $key;
        return getSetting($settingKey);
    }
}
