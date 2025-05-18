<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 4:12 PM
 */

namespace Alif\Permissions\Macros;

use Illuminate\Routing\Route;

class PermissionMacro
{
    public static function register(): void
    {
        Route::macro('role', function (array|string $roles = []) {
            $roles = implode('|', \Arr::wrap($roles));

            $this->middleware("role:$roles");

            return $this;
        });

        Route::macro('permission', function (array|string $permissions = []) {
            $permissions = implode('|', \Arr::wrap($permissions));

            $this->middleware("permission:$permissions");

            return $this;
        });
    }
}
