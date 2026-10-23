<?php

namespace Alif\Permissions\Tests;

use Alif\Permissions\Models\Permission;
use Alif\Permissions\Models\Role;
use Alif\Permissions\PermissionServiceProvider;
use Alif\Permissions\Tests\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Run the test suite against UUID primary keys or auto-incrementing ones.
     */
    protected bool $usesUuid = true;

    protected function getPackageProviders($app): array
    {
        return [PermissionServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('permissions.is_model_uuid', $this->usesUuid);
        $app['config']->set('permissions.cacheable', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->createUsersTable();

        $this->usesUuid
                ? (require __DIR__ . '/../database/migrations/create_permissions_table.php')->up()
                : $this->createIntegerKeyedTables();
    }

    /**
     * Count the queries executed by the given callback.
     */
    protected function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    protected function createUser(string $name = 'John'): User
    {
        return User::create(['name' => $name, 'email' => $name . '@example.com', 'password' => 'secret']);
    }

    protected function createRole(string $name, ?string $sCode = null): Role
    {
        return Role::create(['name' => $name, 's_code' => $sCode]);
    }

    protected function createPermission(string $name): Permission
    {
        return Permission::create(['name' => $name]);
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $this->usesUuid ? $table->uuid('id')->primary() : $table->id();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
        });
    }

    /**
     * The published migration ships with UUID keys, this is its bigint variant.
     */
    private function createIntegerKeyedTables(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->string('s_code')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });
    }
}
