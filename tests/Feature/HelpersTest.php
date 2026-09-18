<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Models\Role;
use Alif\Permissions\Tests\Fixtures\PlainUser;
use Alif\Permissions\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_check_to_uuid(): void
    {
        $this->assertTrue(checkToUUID(\Ramsey\Uuid\Uuid::uuid4()->toString()));
        $this->assertFalse(checkToUUID('not-a-uuid'));
        $this->assertFalse(checkToUUID(''));
        $this->assertFalse(checkToUUID(42));
        $this->assertFalse(checkToUUID(null));
    }

    public function test_permission_cacheable(): void
    {
        $this->assertTrue(permissionCacheable());

        config()->set('permissions.cacheable', false);

        $this->assertFalse(permissionCacheable());
    }

    public function test_is_super_admin(): void
    {
        $this->assertFalse(isSuperAdmin(), 'a guest is never a super admin');

        $this->createRole(Role::SUPER_ADMIN, 'super');

        $user = $this->createUser();
        $this->actingAs($user);
        $this->assertFalse(isSuperAdmin());

        $user->assignRoles(Role::SUPER_ADMIN);
        $this->assertTrue(isSuperAdmin());
    }

    public function test_is_super_admin_with_a_user_model_without_the_trait(): void
    {
        $this->actingAs(PlainUser::create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']));

        $this->assertFalse(isSuperAdmin());
    }
}
