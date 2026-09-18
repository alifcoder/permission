<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 */

namespace Alif\Permissions\Support;

use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Single entry point for every cache read/write of the package.
 *
 * Every entry is tagged with:
 *  - "alif_permission"            -> lets us drop the whole package cache at once
 *  - "user_role:{table}:{key}"    -> lets us drop the cache of a single user
 *
 * ATTENTION! Tags require a taggable cache store (redis, memcached, array).
 */
class PermissionCache
{
    /**
     * Tag applied to every entry created by the package.
     */
    public const TAG = 'alif_permission';

    /**
     * Incremented on every flush, so that objects still living in memory
     * (queue workers, Octane, long running commands) drop their own memo.
     */
    private static int $generation = 0;

    /**
     * Current generation of the cache.
     */
    public static function generation(): int
    {
        return static::$generation;
    }

    /**
     * Check whether the package is allowed to cache on the configured store.
     */
    public static function enabled(): bool
    {
        if (config('permissions.cacheable', true) !== true) {
            return false;
        }

        return static::store()->getStore() instanceof TaggableStore;
    }

    /**
     * Cache repository used by the package.
     */
    public static function store(): Repository
    {
        return Cache::store(config('permissions.cache_store'));
    }

    /**
     * Cache key of the given model, scoped by table to avoid collisions
     * between different models sharing the same primary key.
     */
    public static function key(Model $model, string $suffix): string
    {
        return $suffix . ':' . $model->getTable() . ':' . $model->getKey();
    }

    /**
     * Tag identifying every entry that belongs to the given user.
     */
    public static function userTag(Model $model): string
    {
        return 'user_role:' . $model->getTable() . ':' . $model->getKey();
    }

    /**
     * Remember a value for the given user.
     */
    public static function remember(Model $model, string $key, Closure $callback): mixed
    {
        $store = static::store()->tags([static::TAG, static::userTag($model)]);
        $ttl   = config('permissions.cache_ttl');

        return $ttl === null
                ? $store->rememberForever($key, $callback)
                : $store->remember($key, $ttl, $callback);
    }

    /**
     * Drop every cached entry of a single user.
     */
    public static function flushUser(Model $model): void
    {
        static::$generation++;

        if (static::enabled() === false || $model->getKey() === null) {
            return;
        }

        static::store()->tags([static::userTag($model)])->flush();
    }

    /**
     * Drop every cached entry of the package.
     *
     * Used when a role or a permission changes, because such a change may
     * affect any user of the application.
     */
    public static function flush(): void
    {
        static::$generation++;

        if (static::enabled() === false) {
            return;
        }

        static::store()->tags([static::TAG])->flush();
    }
}
