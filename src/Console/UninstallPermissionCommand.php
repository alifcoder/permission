<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 5:39 PM
 */

namespace Alif\Permissions\Console;

use Illuminate\Console\Command;

class UninstallPermissionCommand extends Command
{
    protected $signature = 'permission:uninstall';

    protected $description = 'Remove config, migrations, and data related to Permission package';

    public function handle(): void
    {
        // Delete published config
        $configPath = config_path('permissions.php');
        if (file_exists($configPath)) {
            unlink($configPath);
            $this->info('⚠️ Removed config/permissions.php');
        }

        // Delete published migrations
        $migrations = collect(glob(database_path('migrations/*create_permissions_table*.php')))
                ->sortDesc(); // Sort by newest first

        foreach ($migrations as $migrationPath) {
            $migrationInstance = require $migrationPath;

            if (method_exists($migrationInstance, 'down')) {
                try {
                    $migrationInstance->down();
                    $this->info("🔧 Rolled back anonymous migration in: {$migrationPath}");
                } catch (\Throwable $e) {
                    $this->error("❌ Failed to rollback anonymous migration: {$e->getMessage()}");
                }
            }

            // Delete the file after rollback
            unlink($migrationPath);
            $this->info("⚠️ Deleted migration: {$migrationPath}");
        }

        $this->info('✅  Permission package uninstalled successfully.');
    }

}
