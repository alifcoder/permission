<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 4:32 PM
 */

namespace Alif\Permissions\Middleware;

use Alif\Permissions\Exceptions\PermissionException;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * @throws PermissionException
     */
    public function handle(Request $request, Closure $next, string $roles, ?string $guard = null)
    {
        // auth guard
        $authGuard = app('auth')->guard($guard);

        // check user is guest then throw exception
        if ($authGuard->guest()) {
            throw PermissionException::notLoggedIn();
        }

        // check user is super admin then allow all roles
        if ($authGuard->user()->isSuperAdmin()) {
            return $next($request);
        }

        // prepare roles for checking
        $roles = explode('|', $roles);

        // check user has all roles then allow
        if ($authGuard->user()->hasAllRoles($roles) === true) {
            return $next($request);
        }

        // else throw exception
        throw PermissionException::roles();
    }
}
