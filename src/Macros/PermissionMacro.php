<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 4:12 PM
 */

namespace Alif\Permissions\Macros;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;

class PermissionMacro
{
    public static function register(): void
    {
        if (Route::hasMacro('role') === false) {
            Route::macro('role', function (array|string $roles = []) {
                /** @var Route $this */
                return $this->middleware('role:' . implode('|', Arr::wrap($roles)));
            });
        }

        if (Route::hasMacro('permission') === false) {
            Route::macro('permission', function (array|string $permissions = []) {
                /** @var Route $this */
                return $this->middleware('permission:' . implode('|', Arr::wrap($permissions)));
            });
        }
    }
}
