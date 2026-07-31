<?php

namespace App\Base\Traits;

use Illuminate\Support\Facades\Cache;
use Predis\Connection\ConnectionException;

trait CacheTrait
{
    use HandlerLog;

    public function cache(string $key, callable $callback, ?int $ttl = null): mixed
    {
        try {
            if (config('api.cache.use_cache')) {
                return Cache::remember($key, $ttl ?? config('api.cache.ttl'), $callback);
            }

            return $callback();
        } catch (ConnectionException $exception) {
            return $callback();
        }
    }

    public function clearUserCache($user_id): void
    {
        Cache::forget('user_id_'.$user_id);
    }

    public function clearAccessToken($access_token): void
    {
        Cache::forget('token_'.$access_token);
    }

    public function clearRefreshToken($refresh_token): void
    {
        Cache::forget('refresh_token_'.$refresh_token);
    }

    public function clearCache(string $prefix, string $key): void
    {
        Cache::forget($prefix.$key);
    }

    /**
     * Versão atual de um grupo de chaves cacheadas (ex.: todas as páginas/
     * filtros da listagem de tasks de um usuário, ou da listagem de admin
     * de usuários). Callers embutem essa versão na própria chave de cache
     * (`"{$tag}_v{$version}_" . md5(...)`); `bumpCacheVersion()` invalida
     * todas de uma vez só incrementando o contador — evita depender de
     * `Cache::forget()` com wildcard (que nunca funcionou: o forget só
     * apaga chave exata, nunca um prefixo, não importa o driver) e
     * funciona igual em qualquer driver de cache (database, array, redis).
     */
    public function cacheVersion(string $tag): int
    {
        return (int) Cache::get("cache_version_{$tag}", 1);
    }

    public function bumpCacheVersion(string $tag): void
    {
        Cache::add("cache_version_{$tag}", 1);
        Cache::increment("cache_version_{$tag}");
    }
}
