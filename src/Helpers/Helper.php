<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 10:14 AM
 */


if (!function_exists('checkToUUID')) {
    function checkToUUID(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
    }
}

if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin(): bool
    {
        return auth()->user()?->hasAllRoles(\Alif\Permissions\Models\Role::SUPER_ADMIN) ?? false;
    }
}

if (!function_exists('permissionCacheable')) {
    function permissionCacheable(): bool
    {
        if (config('permissions.cacheable') === false) {
            return false;
        }

        $allow = [
                'redis',
                'memcached',
        ];

        return in_array(config('cache.default'), $allow);
    }
}