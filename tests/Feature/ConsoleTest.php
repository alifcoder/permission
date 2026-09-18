<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ConsoleTest extends TestCase
{
    public function test_it_publishes_and_uninstalls_the_package_files(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'permissions'])->assertSuccessful();

        $this->assertFileExists(config_path('permissions.php'));
        $this->assertNotEmpty(File::glob(database_path('migrations/*create_permissions_table*.php')));
        $this->assertFileExists(resource_path('lang/vendor/permissions/en/permissions.php'));

        $this->artisan('permission:uninstall')->assertSuccessful();

        $this->assertFileDoesNotExist(config_path('permissions.php'));
        $this->assertEmpty(File::glob(database_path('migrations/*create_permissions_table*.php')));
        $this->assertDirectoryDoesNotExist(resource_path('lang/vendor/permissions'));
    }

    public function test_it_asks_for_a_confirmation_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('permission:uninstall')
                ->expectsConfirmation('Are you sure you want to run this command?', 'no')
                ->assertFailed();
    }
}
