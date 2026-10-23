<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Support\PermissionCache;
use Alif\Permissions\Tests\Fixtures\User;
use Alif\Permissions\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class ScopedInvalidationTest extends TestCase
{
    public function test_changing_a_role_keeps_the_cache_of_the_users_without_it(): void
    {
        $admin   = $this->createRole('Admin', 'admin');
        $manager = $this->createRole('Manager', 'manager');

        $john = $this->createUser('john');
        $john->assignRoles($admin);

        $jane = $this->createUser('jane');
        $jane->assignRoles($manager);

        User::find($jane->id)->hasAllRoles('manager');              // warm

        $admin->givePermissionTo($this->createPermission('products.update'));

        $queries = $this->countQueries(function () use ($jane) {
            $fresh = User::find($jane->id);
            $fresh->hasAllRoles('manager');
            $fresh->hasAllPermissions([]);
        });

        $this->assertSame(1, $queries, 'only the User::find() query is expected');
    }

    public function test_changing_a_role_refetches_the_cache_of_the_users_having_it(): void
    {
        $admin = $this->createRole('Admin', 'admin');

        $john = $this->createUser('john');
        $john->assignRoles($admin);

        User::find($john->id)->hasAllRoles('admin');                // warm

        $admin->givePermissionTo($this->createPermission('products.update'));

        $queries = $this->countQueries(fn() => User::find($john->id)->hasAllRoles('admin'));

        $this->assertGreaterThan(1, $queries, 'the roles and their permissions are read again');
        $this->assertTrue(User::find($john->id)->hasAnyPermission('products.update'));
    }

    public function test_renaming_a_permission_keeps_the_cache_of_the_users_without_it(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $admin->syncPermissions([$this->createPermission('products.read')]);

        $manager = $this->createRole('Manager', 'manager');
        $manager->syncPermissions([$this->createPermission('orders.read')]);

        $jane = $this->createUser('jane');
        $jane->assignRoles($manager);

        User::find($jane->id)->permissionNames();                   // warm

        $admin->permissions()->first()->update(['name' => 'products.view']);

        $queries = $this->countQueries(fn() => User::find($jane->id)->hasAllPermissions('orders.read'));

        $this->assertSame(1, $queries, 'only the User::find() query is expected');
    }

    public function test_a_stale_version_is_rejected_even_when_the_entry_was_written_by_another_instance(): void
    {
        $admin = $this->createRole('Admin', 'admin');

        $john = $this->createUser('john');
        $john->assignRoles($admin);
        $john->roles;                                               // warm

        // the role is changed through another instance, the cache entry of john is not touched
        $admin->newQuery()->find($admin->id)->givePermissionTo($this->createPermission('products.update'));

        $this->assertTrue(User::find($john->id)->hasAnyPermission('products.update'));
    }

    public function test_force_deleting_a_role_drops_the_cache_of_its_users(): void
    {
        $admin = $this->createRole('Admin', 'admin');

        $john = $this->createUser('john');
        $john->assignRoles($admin);

        $this->assertTrue(User::find($john->id)->hasAllRoles('admin'));           // warm

        $admin->forceDelete();

        $this->assertFalse(User::find($john->id)->hasAnyRole('admin'));
    }

    public function test_an_evicted_version_token_invalidates_the_entries_written_before(): void
    {
        $admin = $this->createRole('Admin', 'admin');

        $john = $this->createUser('john');
        $john->assignRoles($admin);

        User::find($john->id)->hasAllRoles('admin');                              // warm

        // the store evicts the token of the role, then the role is changed
        Cache::store()->forget(PermissionCache::versionKey('role', $admin->getKey()));
        $admin->givePermissionTo($this->createPermission('products.update'));

        $this->assertTrue(User::find($john->id)->hasAnyPermission('products.update'));
    }
}
