<?php

namespace Alif\Permissions;

use Alif\Permissions\Console\ClearPermissionCacheCommand;
use Alif\Permissions\Console\UninstallPermissionCommand;
use Alif\Permissions\Macros\PermissionMacro;
use Alif\Permissions\Middleware\PermissionMiddleware;
use Alif\Permissions\Middleware\RoleMiddleware;
use Gate;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // register config
        $this->mergeConfigFrom(__DIR__ . '/../config/permissions.php', 'permissions');

        // Register the middlewares
        $router = $this->app['router'];
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role', RoleMiddleware::class);


        // Register the console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                                    UninstallPermissionCommand::class,
                                    ClearPermissionCacheCommand::class,
                            ]);
        }
    }

    public function boot(): void
    {
        // Register the macro
        PermissionMacro::register();


        // Publish migrations and config
        $timestamp = date('Y_m_d_His');
        $this->publishes([(__DIR__ . '/../config/permissions.php')                           => config_path('permissions.php'),
                          (__DIR__ . '/../resources/lang')                                   => resource_path('lang/vendor/permissions'),
                          (__DIR__ . '/../database/migrations/create_permissions_table.php') => database_path("migrations/{$timestamp}_create_permissions_table.php"),],
                         'permissions');

        // Define Super Admin
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        // register lang
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'permissions');
    }
}
