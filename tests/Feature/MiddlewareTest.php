<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Models\Role;
use Alif\Permissions\Tests\Fixtures\PlainUser;
use Alif\Permissions\Tests\TestCase;
use Illuminate\Routing\Router;

class MiddlewareTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->get('/single-role', fn() => 'ok')->role('admin');
        $router->get('/many-roles', fn() => 'ok')->role(['admin', 'manager']);
        $router->get('/no-role', fn() => 'ok')->role();
        $router->get('/single-permission', fn() => 'ok')->permission('products.read');
        $router->get('/many-permissions', fn() => 'ok')->permission(['products.read', 'products.update']);
    }

    public function test_a_guest_is_rejected(): void
    {
        $this->get('/single-role')->assertStatus(401);
        $this->get('/single-permission')->assertStatus(401);
    }

    public function test_a_guest_gets_a_json_response_on_a_json_request(): void
    {
        $this->getJson('/single-role')
                ->assertStatus(401)
                ->assertExactJson(['message' => __('permissions::permissions.not_logged_in')]);
    }

    public function test_a_user_without_the_role_is_rejected(): void
    {
        $this->createRole('Admin', 'admin');

        $this->actingAs($this->createUser())
                ->getJson('/single-role')
                ->assertStatus(403)
                ->assertExactJson(['message' => __('permissions::permissions.you_dont_have_role')]);
    }

    public function test_a_user_without_the_permission_is_rejected(): void
    {
        $this->actingAs($this->createUser())
                ->getJson('/single-permission')
                ->assertStatus(403)
                ->assertExactJson(['message' => __('permissions::permissions.you_dont_have_permission')]);
    }

    public function test_a_user_with_the_role_passes(): void
    {
        $user = $this->createUser();
        $user->assignRoles($this->createRole('Admin', 'admin'));

        $this->actingAs($user)->get('/single-role')->assertOk()->assertSee('ok');
    }

    public function test_every_listed_role_is_required(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $this->createRole('Manager', 'manager');

        $user = $this->createUser();
        $user->assignRoles($admin);

        $this->actingAs($user)->get('/many-roles')->assertStatus(403);

        $user->assignRoles('manager');

        $this->actingAs($user)->get('/many-roles')->assertOk();
    }

    public function test_every_listed_permission_is_required(): void
    {
        $admin = $this->createRole('Admin', 'admin');
        $admin->syncPermissions([$this->createPermission('products.read')]);
        $update = $this->createPermission('products.update');

        $user = $this->createUser();
        $user->assignRoles($admin);

        $this->actingAs($user)->get('/single-permission')->assertOk();
        $this->actingAs($user)->get('/many-permissions')->assertStatus(403);

        $admin->givePermissionTo($update);

        $this->actingAs($user)->get('/many-permissions')->assertOk();
    }

    public function test_an_empty_role_list_is_rejected(): void
    {
        $this->actingAs($this->createUser())->get('/no-role')->assertStatus(403);
    }

    public function test_the_super_admin_passes_every_check(): void
    {
        $this->createRole(Role::SUPER_ADMIN, 'super');

        $user = $this->createUser();
        $user->assignRoles(Role::SUPER_ADMIN);

        $this->actingAs($user)->get('/single-role')->assertOk();
        $this->actingAs($user)->get('/many-roles')->assertOk();
        $this->actingAs($user)->get('/many-permissions')->assertOk();
    }

    public function test_a_user_model_without_the_trait_is_rejected(): void
    {
        $user = PlainUser::create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        $this->actingAs($user)->get('/single-role')->assertStatus(403);
        $this->actingAs($user)->get('/single-permission')->assertStatus(403);
    }
}
