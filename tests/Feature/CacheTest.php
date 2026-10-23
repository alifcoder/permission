<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Models\Role;
use Alif\Permissions\Models\Permission;
use Alif\Permissions\Support\PermissionCache;
use Alif\Permissions\Tests\Fixtures\User;
use Alif\Permissions\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CacheTest extends TestCase
{
    private Role $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createRole('Admin', 'admin');
        $this->admin->syncPermissions([$this->createPermission('products.read')]);
    }

    public function test_the_relation_is_never_cached(): void
    {
        $user = $this->createUser();

        $this->assertInstanceOf(BelongsToMany::class, $user->roles());
        $this->assertNotSame($user->roles(), $user->roles());
    }

    public function test_it_reads_the_roles_of_a_user_only_once_per_request(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);

        $fresh = User::find($user->id);

        // roles + their permissions
        $this->assertSame(2, $this->countQueries(fn() => $fresh->hasAllRoles('admin')));

        $this->assertSame(0, $this->countQueries(function () use ($fresh) {
            $fresh->hasAllRoles('admin');
            $fresh->hasAnyRole([$this->admin, 'manager']);
            $fresh->hasAllPermissions('products.read');
            $fresh->hasAnyPermission('products.read');
            $fresh->permissions;
            $fresh->permissionNames();
            $fresh->isSuperAdmin();
        }));
    }

    public function test_it_reads_the_roles_of_a_user_only_once_per_application(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);

        User::find($user->id)->hasAllRoles('admin');            // warm

        $queries = $this->countQueries(function () use ($user) {
            $fresh = User::find($user->id);
            $fresh->hasAllRoles('admin');
            $fresh->permissionNames();
        });

        $this->assertSame(1, $queries, 'only the User::find() query is expected');
    }

    public function test_cached_roles_are_plain_data_and_restore_their_models_and_pivots(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);
        $user->roles; // warm the cache

        $key = PermissionCache::key($user, 'roles');
        $store = PermissionCache::store()->tags([PermissionCache::TAG, PermissionCache::userTag($user)]);
        $payload = $store->get($key);

        $this->assertIsArray($payload);
        array_walk_recursive($payload, fn($value) => $this->assertTrue(is_scalar($value) || $value === null));

        $fresh = User::find($user->id);
        $this->assertSame(0, $this->countQueries(fn() => $fresh->roles));

        $roles = $fresh->roles;
        $this->assertInstanceOf(EloquentCollection::class, $roles);
        $this->assertInstanceOf(Role::class, $roles->first());
        $this->assertSame($user->id, $roles->first()->pivot->user_id);
        $this->assertInstanceOf(Permission::class, $roles->first()->permissions->first());
        $this->assertSame($this->admin->id, $roles->first()->permissions->first()->pivot->role_id);
        $this->assertSame(['products.read'], $fresh->permissionNames());
    }

    public function test_an_incomplete_legacy_cache_entry_is_replaced(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);

        $key = PermissionCache::key($user, 'roles');
        $store = PermissionCache::store()->tags([PermissionCache::TAG, PermissionCache::userTag($user)]);
        $store->forever($key, unserialize('O:7:"Missing":0:{}'));

        $fresh = User::find($user->id);
        $this->assertTrue($fresh->hasAllRoles('admin'));
        $this->assertIsArray($store->get($key));
        $this->assertSame(1, $store->get($key)['version']);
    }

    public function test_it_caches_an_empty_role_set(): void
    {
        $user = $this->createUser();

        $this->assertSame(1, $this->countQueries(fn() => $user->roles));
        $this->assertSame(1, $this->countQueries(fn() => User::find($user->id)->roles), 'only the User::find() query is expected');
    }

    public function test_an_eager_loaded_relation_wins_over_the_cache(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);
        $user->roles;

        $user->roles()->detach();

        $this->assertCount(1, $user->roles, 'the stale cache is still used');
        $this->assertCount(0, User::with('roles')->find($user->id)->roles);
    }

    public function test_assigning_syncing_and_removing_roles_drops_the_cache_of_the_user(): void
    {
        $manager = $this->createRole('Manager', 'manager');

        $user = $this->createUser();
        $user->hasAnyRole('admin');                                  // warm

        $user->assignRoles($this->admin);
        $this->assertTrue(User::find($user->id)->hasAllRoles('admin'));

        $user->syncRoles($manager);
        $this->assertFalse(User::find($user->id)->hasAnyRole('admin'));
        $this->assertTrue(User::find($user->id)->hasAllRoles('manager'));

        $user->removeRole($manager);
        $this->assertFalse(User::find($user->id)->hasAnyRole('manager'));
    }

    public function test_the_cache_of_one_user_is_not_shared_with_another(): void
    {
        $john = $this->createUser('john');
        $jane = $this->createUser('jane');

        $john->assignRoles($this->admin);

        $this->assertTrue(User::find($john->id)->hasAllRoles('admin'));
        $this->assertFalse(User::find($jane->id)->hasAnyRole('admin'));

        $jane->assignRoles($this->admin);

        $this->assertTrue(User::find($jane->id)->hasAllRoles('admin'));
        $this->assertTrue(User::find($john->id)->hasAllRoles('admin'));
    }

    public function test_changing_the_permissions_of_a_role_drops_the_cache_of_its_users(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);

        $this->assertFalse(User::find($user->id)->hasAnyPermission('products.update'));

        $update = $this->createPermission('products.update');
        $this->admin->givePermissionTo($update);
        $this->assertTrue(User::find($user->id)->hasAnyPermission('products.update'));

        $this->admin->revokePermissionTo($update);
        $this->assertFalse(User::find($user->id)->hasAnyPermission('products.update'));

        $this->admin->syncPermissions([$update]);
        $this->assertSame(['products.update'], User::find($user->id)->permissionNames());
    }

    public function test_updating_a_role_drops_the_cache_of_its_users(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);
        $user->hasAllRoles('admin');

        $this->admin->update(['name' => 'Administrator', 's_code' => 'administrator']);

        $this->assertTrue(User::find($user->id)->hasAllRoles('administrator'));
        $this->assertTrue(User::find($user->id)->hasAllRoles('ADMINISTRATOR'));
        $this->assertFalse(User::find($user->id)->hasAnyRole('admin'));
    }

    public function test_deleting_and_restoring_a_role_drops_the_cache_of_its_users(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);

        $this->assertTrue(User::find($user->id)->hasAllRoles('admin'));

        $this->admin->delete();
        $this->assertFalse(User::find($user->id)->hasAnyRole('admin'));

        $this->admin->restore();
        $this->assertTrue(User::find($user->id)->hasAllRoles('admin'));
    }

    public function test_renaming_a_permission_drops_the_cache(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);

        $this->assertTrue(User::find($user->id)->hasAllPermissions('products.read'));

        $this->admin->permissions()->first()->update(['name' => 'products.view']);

        $this->assertFalse(User::find($user->id)->hasAnyPermission('products.read'));
        $this->assertTrue(User::find($user->id)->hasAllPermissions('products.view'));
    }

    public function test_deleting_a_user_drops_its_cache(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);
        $user->roles;

        $key = PermissionCache::key($user, 'roles');
        $tag = PermissionCache::userTag($user);

        $this->assertNotNull(PermissionCache::store()->tags([PermissionCache::TAG, $tag])->get($key));

        $user->delete();

        $this->assertNull(PermissionCache::store()->tags([PermissionCache::TAG, $tag])->get($key));
    }

    public function test_a_model_instance_forgets_what_it_resolved_before_a_flush(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);

        $this->assertTrue($user->hasAllRoles('admin'));                  // memoised on this instance
        $this->assertSame(['products.read'], $user->permissionNames());

        // another instance of the same user changes the roles
        User::find($user->id)->assignRoles($this->createRole('Manager', 'manager'));

        $this->assertTrue($user->hasAllRoles(['admin', 'manager']));

        // the permissions of a role change
        $this->admin->givePermissionTo($this->createPermission('products.update'));

        $this->assertEqualsCanonicalizing(['products.read', 'products.update'], $user->permissionNames());
    }

    public function test_the_cache_can_be_flushed_by_hand(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);
        $user->roles;

        $user->roles()->detach();
        $this->assertTrue($user->hasAllRoles('admin'), 'the cache is stale on purpose');

        $user->forgetPermissionCache();
        $this->assertFalse($user->hasAnyRole('admin'));
    }

    public function test_the_command_flushes_the_cache(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->admin);
        $user->roles;

        $this->artisan('permission:cache-clear')
                ->expectsOutputToContain('cleared successfully')
                ->assertSuccessful();

        $this->assertNull(PermissionCache::store()
                ->tags([PermissionCache::TAG, PermissionCache::userTag($user)])
                ->get(PermissionCache::key($user, 'roles')));
    }
}
