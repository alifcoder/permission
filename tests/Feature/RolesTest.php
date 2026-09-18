<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Models\Role;
use Alif\Permissions\Tests\TestCase;

class RolesTest extends TestCase
{
    public function test_it_creates_a_role_with_an_upper_cased_name_and_a_uuid(): void
    {
        $role = $this->createRole('Admin', 'admin');

        $this->assertSame('ADMIN', $role->name);
        $this->assertSame('admin', $role->s_code);
        $this->assertTrue(checkToUUID($role->id));
    }

    public function test_it_assigns_roles_by_any_kind_of_value(): void
    {
        $admin   = $this->createRole('Admin', 'admin');
        $manager = $this->createRole('Manager', 'manager');

        $user = $this->createUser();
        $user->assignRoles($admin);                       // model
        $user->assignRoles($manager->id);                 // key
        $user->assignRoles('admin');                      // s_code, already assigned

        $this->assertSame(2, $user->roles()->count());
        $this->assertEqualsCanonicalizing(['admin', 'manager'], $user->roles->pluck('s_code')->all());
    }

    public function test_it_assigns_roles_from_arrays_and_collections(): void
    {
        $admin   = $this->createRole('Admin', 'admin');
        $manager = $this->createRole('Manager', 'manager');

        $user = $this->createUser();
        $user->assignRoles(collect(['admin', $manager]));

        $this->assertTrue($user->hasAllRoles([$admin, 'manager']));

        $user->assignRoles(Role::all());

        $this->assertSame(2, $user->roles()->count());
    }

    public function test_it_ignores_unknown_roles(): void
    {
        $user = $this->createUser();
        $user->assignRoles(['does-not-exist', '', null, 3.14]);

        $this->assertSame(0, $user->roles()->count());
    }

    public function test_it_matches_a_role_by_key_name_or_s_code(): void
    {
        $admin = $this->createRole('Admin', 'admin');

        $user = $this->createUser();
        $user->assignRoles($admin);

        $this->assertTrue($user->hasAllRoles($admin));
        $this->assertTrue($user->hasAllRoles($admin->id));
        $this->assertTrue($user->hasAllRoles('admin'));
        $this->assertTrue($user->hasAllRoles('ADMIN'));
        $this->assertTrue($user->hasAllRoles('aDmIn'));
        $this->assertFalse($user->hasAllRoles('manager'));
    }

    public function test_has_all_roles_requires_every_role(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $this->createRole('Manager', 'manager');

        $user = $this->createUser();
        $user->assignRoles($admin);

        $this->assertTrue($user->hasAllRoles(['admin']));
        $this->assertFalse($user->hasAllRoles(['admin', 'manager']));
    }

    public function test_has_any_role_requires_a_single_role(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->createRole('Admin', 'admin'));

        $this->assertTrue($user->hasAnyRole(['manager', 'admin']));
        $this->assertFalse($user->hasAnyRole(['manager', 'editor']));
    }

    public function test_role_checks_fail_closed_on_an_empty_value(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->createRole('Admin', 'admin'));

        $this->assertFalse($user->hasAllRoles([]));
        $this->assertFalse($user->hasAnyRole([]));
        $this->assertFalse($user->hasAllRoles(''));
        $this->assertFalse($user->hasAnyRole(collect()));
    }

    public function test_it_syncs_roles(): void
    {
        $admin   = $this->createRole('Admin', 'admin');
        $manager = $this->createRole('Manager', 'manager');

        $user = $this->createUser();
        $user->assignRoles($admin);
        $user->syncRoles($manager);

        $this->assertFalse($user->hasAnyRole('admin'));
        $this->assertTrue($user->hasAllRoles('manager'));

        $user->syncRoles([]);

        $this->assertSame(0, $user->roles()->count());
    }

    public function test_it_removes_roles(): void
    {
        $admin   = $this->createRole('Admin', 'admin');
        $manager = $this->createRole('Manager', 'manager');

        $user = $this->createUser();
        $user->assignRoles([$admin, $manager]);
        $user->removeRole('admin');

        $this->assertSame(['manager'], $user->roles->pluck('s_code')->all());

        $user->removeRole($manager->id);

        $this->assertSame(0, $user->roles()->count());
    }

    public function test_it_detects_the_super_admin(): void
    {
        $user = $this->createUser();

        $this->assertFalse($user->isSuperAdmin());

        $this->createRole(Role::SUPER_ADMIN, 'super');
        $user->assignRoles(Role::SUPER_ADMIN);

        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_it_resolves_the_role_model_from_the_config(): void
    {
        $this->assertInstanceOf(config('permissions.models.role'), $this->createUser()->roles()->getRelated());
    }
}
