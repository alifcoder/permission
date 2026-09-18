<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Models\Permission;
use Alif\Permissions\Tests\TestCase;

class PermissionsTest extends TestCase
{
    public function test_it_gives_syncs_and_revokes_permissions_of_a_role(): void
    {
        $role   = $this->createRole('Admin', 'admin');
        $read   = $this->createPermission('products.read');
        $update = $this->createPermission('products.update');

        $role->givePermissionTo($read);
        $role->givePermissionTo($read->id);                     // idempotent
        $this->assertSame(['products.read'], $role->permissions()->pluck('name')->all());

        $role->givePermissionTo('products.update');
        $this->assertEqualsCanonicalizing(['products.read', 'products.update'], $role->permissions()->pluck('name')->all());

        $role->revokePermissionTo($update);
        $this->assertSame(['products.read'], $role->permissions()->pluck('name')->all());

        $role->syncPermissions(['products.update']);
        $this->assertSame(['products.update'], $role->permissions()->pluck('name')->all());

        $role->syncPermissions([]);
        $this->assertSame(0, $role->permissions()->count());
    }

    public function test_it_ignores_unknown_permissions(): void
    {
        $role = $this->createRole('Admin', 'admin');
        $role->givePermissionTo('does.not.exist');

        $this->assertSame(0, $role->permissions()->count());
    }

    public function test_it_collects_the_unique_permissions_of_all_roles(): void
    {
        $read   = $this->createPermission('products.read');
        $update = $this->createPermission('products.update');

        $admin = $this->createRole('Admin', 'admin');
        $admin->syncPermissions([$read, $update]);

        $manager = $this->createRole('Manager', 'manager');
        $manager->syncPermissions([$read]);

        $user = $this->createUser();
        $user->assignRoles([$admin, $manager]);

        $this->assertCount(2, $user->permissions);
        $this->assertContainsOnlyInstancesOf(Permission::class, $user->permissions);
        $this->assertEqualsCanonicalizing(['products.read', 'products.update'], $user->permissionNames());
    }

    public function test_it_checks_all_and_any_permissions(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $admin->syncPermissions([$this->createPermission('products.read')]);
        $this->createPermission('products.update');

        $user = $this->createUser();
        $user->assignRoles($admin);

        $this->assertTrue($user->hasAllPermissions('products.read'));
        $this->assertTrue($user->hasAllPermissions(['products.read']));
        $this->assertFalse($user->hasAllPermissions(['products.read', 'products.update']));

        $this->assertTrue($user->hasAnyPermission(['products.update', 'products.read']));
        $this->assertFalse($user->hasAnyPermission(['products.update']));
    }

    public function test_permission_checks_fail_closed_on_an_empty_value(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $admin->syncPermissions([$this->createPermission('products.read')]);

        $user = $this->createUser();
        $user->assignRoles($admin);

        $this->assertFalse($user->hasAllPermissions([]));
        $this->assertFalse($user->hasAnyPermission([]));
        $this->assertFalse($user->hasAllPermissions(''));
    }

    public function test_a_user_without_roles_has_no_permissions(): void
    {
        $user = $this->createUser();

        $this->assertTrue($user->permissions->isEmpty());
        $this->assertSame([], $user->permissionNames());
        $this->assertFalse($user->hasAnyPermission('products.read'));
    }

    public function test_the_super_admin_bypasses_the_gate(): void
    {
        $this->createRole(\Alif\Permissions\Models\Role::SUPER_ADMIN, 'super');

        $user = $this->createUser();
        $this->assertFalse($user->can('anything'));

        $user->assignRoles(\Alif\Permissions\Models\Role::SUPER_ADMIN);
        $this->assertTrue($user->can('anything'));
    }

    public function test_the_gate_survives_guest_checks(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser(null)->allows('anything'));
    }
}
