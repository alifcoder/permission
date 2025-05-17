<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-17
 * Contact: https://t.me/alif_coder
 * Time: 7:28 PM
 */

namespace Alif\Permissions\Console;

use Illuminate\Console\Command;

class ClearPermissionCacheCommand extends Command
{
    protected $signature = 'permission:cache-clear';

    protected $description = 'Clear the permission caches';

    public function handle(): void
    {
        $allow = [
                'redis',
                'memcached',
        ];

        if (in_array(config('cache.default'), $allow) === true) {
            // clear cache by tags
            \Cache::tags(['alif_permission'])->flush();
            $this->info('✅  Permission caches are cleared successfully.');
        } else {
            $this->info('⚠️  Cache driver is not supported.');
        }
    }
}
