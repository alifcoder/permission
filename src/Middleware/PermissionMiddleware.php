<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 4:32 PM
 */

namespace Alif\Permissions\Middleware;

use Alif\Permissions\Exceptions\PermissionException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * @throws PermissionException
     */
    public function handle(Request $request, Closure $next, string $permissions, ?string $guard = null): Response
    {
        $user = app('auth')->guard($guard)->user();

        // check user is guest then throw exception
        if ($user === null) {
            throw PermissionException::notLoggedIn();
        }

        if (method_exists($user, 'hasAllPermissions') === false) {
            throw PermissionException::permissions();
        }

        // check user is super admin then allow all permissions
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // prepare permissions for checking
        $permissions = array_filter(explode('|', $permissions), static fn(string $permission) => trim($permission) !== '');

        // check user has all permissions then allow
        if ($permissions !== [] && $user->hasAllPermissions($permissions) === true) {
            return $next($request);
        }

        // else throw exception
        throw PermissionException::permissions();
    }
}
