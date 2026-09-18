<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Support\PermissionCache;
use Alif\Permissions\Tests\Fixtures\User;
use Alif\Permissions\Tests\TestCase;

class CacheDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('permissions.cacheable', false);
    }

    public function test_the_cache_is_disabled(): void
    {
        $this->assertFalse(PermissionCache::enabled());
        $this->assertFalse(permissionCacheable());
    }

    public function test_everything_works_without_a_cache(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $admin->syncPermissions([$this->createPermission('products.read')]);

        $user = $this->createUser();
        $user->assignRoles('admin');

        $this->assertTrue(User::find($user->id)->hasAllRoles('admin'));
        $this->assertTrue(User::find($user->id)->hasAllPermissions('products.read'));

        $admin->givePermissionTo($this->createPermission('products.update'));
        $this->assertTrue(User::find($user->id)->hasAllPermissions(['products.read', 'products.update']));

        $user->removeRole($admin);
        $this->assertFalse(User::find($user->id)->hasAnyRole('admin'));
    }

    public function test_the_roles_are_still_read_only_once_per_request(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $admin->syncPermissions([$this->createPermission('products.read')]);

        $user = $this->createUser();
        $user->assignRoles($admin);

        $fresh = User::find($user->id);

        $this->assertSame(2, $this->countQueries(fn() => $fresh->hasAllRoles('admin')));
        $this->assertSame(0, $this->countQueries(function () use ($fresh) {
            $fresh->hasAnyRole('admin');
            $fresh->hasAllPermissions('products.read');
            $fresh->permissions;
        }));
    }

    public function test_the_command_reports_a_disabled_cache(): void
    {
        $this->artisan('permission:cache-clear')
                ->expectsOutputToContain('Permission cache is disabled')
                ->assertSuccessful();
    }
}
