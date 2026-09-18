<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 10:14 AM
 */

use Alif\Permissions\Support\PermissionCache;

if (!function_exists('checkToUUID')) {
    /**
     * Check whether the given value is a valid UUID.
     */
    function checkToUUID(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
    }
}

if (!function_exists('isSuperAdmin')) {
    /**
     * Check whether the authenticated user is a super admin.
     */
    function isSuperAdmin(): bool
    {
        $user = auth()->user();

        return $user !== null && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    }
}

if (!function_exists('permissionCacheable')) {
    /**
     * Check whether the package is allowed to cache on the configured store.
     */
    function permissionCacheable(): bool
    {
        return PermissionCache::enabled();
    }
}
