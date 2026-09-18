<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Models\Permission;
use Alif\Permissions\Models\Role;
use Alif\Permissions\Tests\Fixtures\User;
use Alif\Permissions\Tests\TestCase;

class IntegerKeysTest extends TestCase
{
    protected bool $usesUuid = false;

    public function test_the_models_use_auto_incrementing_keys(): void
    {
        $role = $this->createRole('Admin', 'admin');

        $this->assertTrue($role->getIncrementing());
        $this->assertSame('int', $role->getKeyType());
        $this->assertIsInt($role->id);
        $this->assertIsInt($this->createPermission('products.read')->id);
    }

    public function test_it_assigns_and_checks_roles_by_integer_keys(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $admin->givePermissionTo($this->createPermission('products.read')->id);

        $user = $this->createUser();
        $user->assignRoles($admin->id);

        $this->assertTrue($user->hasAllRoles($admin->id));
        $this->assertTrue($user->hasAllRoles((string)$admin->id));
        $this->assertTrue($user->hasAllRoles('admin'));
        $this->assertTrue($user->hasAllPermissions('products.read'));
    }

    public function test_it_normalises_numeric_string_keys(): void
    {
        $admin = $this->createRole('Admin', 'admin');

        $this->assertSame([$admin->id], Role::resolveKeys((string)$admin->id));
        $this->assertSame([$admin->id], Role::resolveKeys([$admin, $admin->id, (string)$admin->id]));
        $this->assertSame([], Role::resolveKeys('does-not-exist'));
        $this->assertSame([], Permission::resolveKeys([]));
    }

    public function test_the_cache_is_invalidated_with_integer_keys(): void
    {
        $admin = $this->createRole('Admin', 'admin');

        $user = $this->createUser();
        $user->assignRoles($admin);
        $this->assertTrue(User::find($user->id)->hasAllRoles('admin'));

        $admin->givePermissionTo($this->createPermission('products.read'));
        $this->assertTrue(User::find($user->id)->hasAllPermissions('products.read'));

        $user->removeRole($admin);
        $this->assertFalse(User::find($user->id)->hasAnyRole('admin'));
    }
}
