<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 */

namespace Alif\Permissions\Support;

use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
     * Prefix of the version tokens of roles and permissions.
     */
    private const VERSION_PREFIX = 'alif_permission_version:';

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
        return self::$generation;
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
        // an empty value (PERMISSION_CACHE_STORE=) means the default store, not a store named ""
        return Cache::store(config('permissions.cache_store') ?: null);
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
     * Read a value cached for the given user.
     */
    public static function get(Model $model, string $key): mixed
    {
        return static::store()->tags([static::TAG, static::userTag($model)])->get($key);
    }

    /**
     * Store a value for the given user.
     */
    public static function put(Model $model, string $key, mixed $value): void
    {
        $store = static::store()->tags([static::TAG, static::userTag($model)]);
        $ttl   = config('permissions.cache_ttl');

        if ($ttl === null) {
            $store->forever($key, $value);

            return;
        }

        $store->put($key, $value, $ttl);
    }

    /**
     * Cache key of the version token of one role or permission.
     */
    public static function versionKey(string $type, mixed $id): string
    {
        return self::VERSION_PREFIX . $type . ':' . $id;
    }

    /**
     * Read the current version tokens of the given keys, creating the missing ones.
     *
     * @param  array<int, string>  $keys
     * @return array<string, string|null>
     */
    public static function snapshot(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $tokens = static::store()->many($keys);

        // a missing token means it was never bumped or got evicted: give it a new one,
        // so that an entry written before the eviction can not match it
        $missing = array_filter($keys, static fn(string $key) => $tokens[$key] === null);

        if ($missing !== []) {
            foreach ($missing as $key) {
                static::store()->forever($key, Str::random(16));
            }

            $tokens = static::store()->many($keys);
        }

        return array_combine($keys, array_map(static fn(string $key) => $tokens[$key] ?? null, $keys));
    }

    /**
     * Check that none of the versions of the snapshot changed since it was taken.
     *
     * @param  array<string, string|null>  $snapshot
     */
    public static function isFresh(array $snapshot): bool
    {
        $current = static::snapshot(array_keys($snapshot));

        foreach ($snapshot as $key => $token) {
            if ($current[$key] !== $token) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark one role or permission as changed. The cached sets which embed it
     * are rejected on their next read, the others stay untouched.
     */
    public static function bump(string $type, mixed $id): void
    {
        self::$generation++;

        if (static::enabled() === false) {
            return;
        }

        static::store()->forever(static::versionKey($type, $id), Str::random(16));
    }

    /**
     * Drop the cached roles of a single user.
     *
     * A user owns one entry, so it is forgotten by its key. A tag flush would scan the
     * tag set, and Laravel 13 fails on an empty set under PHP 8.5 (phpredis returns false).
     */
    public static function flushUser(Model $model): void
    {
        self::$generation++;

        if (static::enabled() === false || $model->getKey() === null) {
            return;
        }

        static::store()->tags([static::TAG, static::userTag($model)])->forget(static::key($model, 'roles'));
    }

    /**
     * Version token that every cached set embeds. Changing it invalidates the whole package cache.
     *
     * Used by the console commands. It replaces a tag flush, which Laravel 13 fails on an
     * empty tag set under PHP 8.5. The old entries are left behind until they are overwritten.
     */
    public static function flush(): void
    {
        self::$generation++;

        if (static::enabled() === false) {
            return;
        }

        static::store()->forever(static::globalVersionKey(), Str::random(16));
    }

    /**
     * Version key that every cached set depends on, changed by flush().
     */
    public static function globalVersionKey(): string
    {
        return self::VERSION_PREFIX . 'all';
    }
}
