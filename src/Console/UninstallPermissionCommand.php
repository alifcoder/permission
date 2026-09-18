<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 5:39 PM
 */

namespace Alif\Permissions\Console;

use Alif\Permissions\Support\PermissionCache;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class UninstallPermissionCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'permission:uninstall {--force : Force the operation to run when in production}';

    protected $description = 'Remove config, migrations, and data related to Permission package';

    public function handle(): int
    {
        if ($this->confirmToProceed() === false) {
            return self::FAILURE;
        }

        $this->removeConfig();
        $this->removeMigrations();
        $this->removeTranslations();

        PermissionCache::flush();

        $this->info('✅  Permission package uninstalled successfully.');

        return self::SUCCESS;
    }

    private function removeConfig(): void
    {
        $configPath = config_path('permissions.php');

        if (File::exists($configPath)) {
            File::delete($configPath);
            $this->info('⚠️ Removed config/permissions.php');
        }
    }

    private function removeMigrations(): void
    {
        // Sort by newest first
        $migrations = collect(File::glob(database_path('migrations/*create_permissions_table*.php')))->sortDesc();

        foreach ($migrations as $migrationPath) {
            $migration = require $migrationPath;

            if (method_exists($migration, 'down')) {
                try {
                    $migration->down();
                    $this->info("🔧 Rolled back migration in: {$migrationPath}");
                } catch (\Throwable $e) {
                    $this->error("❌ Failed to rollback migration: {$e->getMessage()}");
                }
            }

            // Forget the migration, otherwise it is never executed again after a re-install
            if (Schema::hasTable('migrations')) {
                DB::table('migrations')
                        ->where('migration', pathinfo($migrationPath, PATHINFO_FILENAME))
                        ->delete();
            }

            File::delete($migrationPath);
            $this->info("⚠️ Deleted migration: {$migrationPath}");
        }
    }

    private function removeTranslations(): void
    {
        $langPath = resource_path('lang/vendor/permissions');

        if (File::isDirectory($langPath)) {
            File::deleteDirectory($langPath);
            $this->info('⚠️ Removed ' . $langPath);
        }
    }
}
