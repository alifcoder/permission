<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-17
 * Contact: https://t.me/alif_coder
 * Time: 7:28 PM
 */

namespace Alif\Permissions\Console;

use Alif\Permissions\Support\PermissionCache;
use Illuminate\Console\Command;

class ClearPermissionCacheCommand extends Command
{
    protected $signature = 'permission:cache-clear';

    protected $description = 'Clear the permission caches';

    public function handle(): int
    {
        if (PermissionCache::enabled() === false) {
            $this->warn('⚠️  Permission cache is disabled or the cache store does not support tags.');

            return self::SUCCESS;
        }

        PermissionCache::flush();

        $this->info('✅  Permission caches are cleared successfully.');

        return self::SUCCESS;
    }
}
