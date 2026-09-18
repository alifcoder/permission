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

class RoleMiddleware
{
    /**
     * @throws PermissionException
     */
    public function handle(Request $request, Closure $next, string $roles, ?string $guard = null): Response
    {
        $user = app('auth')->guard($guard)->user();

        // check user is guest then throw exception
        if ($user === null) {
            throw PermissionException::notLoggedIn();
        }

        if (method_exists($user, 'hasAllRoles') === false) {
            throw PermissionException::roles();
        }

        // check user is super admin then allow all roles
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // prepare roles for checking
        $roles = array_filter(explode('|', $roles), static fn(string $role) => trim($role) !== '');

        // check user has all roles then allow
        if ($roles !== [] && $user->hasAllRoles($roles) === true) {
            return $next($request);
        }

        // else throw exception
        throw PermissionException::roles();
    }
}
