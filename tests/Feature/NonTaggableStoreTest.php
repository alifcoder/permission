<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Support\PermissionCache;
use Alif\Permissions\Tests\Fixtures\User;
use Alif\Permissions\Tests\TestCase;

class NonTaggableStoreTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // the file store does not support tags
        $app['config']->set('cache.default', 'file');
    }

    public function test_it_does_not_cache_on_a_store_without_tag_support(): void
    {
        $this->assertFalse(PermissionCache::enabled());
    }

    public function test_it_falls_back_to_the_database(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $admin->syncPermissions([$this->createPermission('products.read')]);

        $user = $this->createUser();
        $user->assignRoles($admin);

        $this->assertTrue(User::find($user->id)->hasAllRoles('admin'));
        $this->assertTrue(User::find($user->id)->hasAllPermissions('products.read'));
    }

    public function test_a_taggable_store_can_be_configured_explicitly(): void
    {
        config()->set('permissions.cache_store', 'array');

        $this->assertTrue(PermissionCache::enabled());

        $user = $this->createUser();
        $user->assignRoles($this->createRole('Admin', 'admin'));

        $this->assertTrue(User::find($user->id)->hasAllRoles('admin'));
        $this->assertSame(1, $this->countQueries(fn() => User::find($user->id)->hasAllRoles('admin')));
    }
}
