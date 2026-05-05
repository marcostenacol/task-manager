<?php

namespace App\Base\Traits;

use Illuminate\Support\Facades\Cache;

trait CacheTrait {

    use HandlerLog;

    /**
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function cache(string $key, callable $callback, ?int $ttl = null): mixed
    {
        try {
            if (config('api.cache.use_cache')) {
                return Cache::remember($key, $ttl ?? config('api.cache.ttl'), $callback);
            }
            return $callback();
        } catch (\Predis\Connection\ConnectionException $exception) {
            return $callback();
        }
    }

    /**
     * @param $user_id
     * @return void
     */
    public function clearUserCache($user_id): void {
        Cache::forget('user_id_' . $user_id);
    }

    /**
     * @param $access_token
     * @return void
     */
    public function clearAccessToken($access_token): void {
        Cache::forget('token_' . $access_token);
    }

    /**
     * @param $refresh_token
     * @return void
     */
    public function clearRefreshToken($refresh_token): void {
        Cache::forget('refresh_token_' . $refresh_token);
    }

}
