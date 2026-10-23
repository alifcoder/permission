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

    public function __construct()
    {
        $this->description = __('permissions::permissions.commands.cache_clear.description');

        parent::__construct();
    }

    public function handle(): int
    {
        if (PermissionCache::enabled() === false) {
            $this->warn('⚠️  ' . __('permissions::permissions.commands.cache_clear.disabled'));

            return self::SUCCESS;
        }

        PermissionCache::flush();

        $this->info('✅  ' . __('permissions::permissions.commands.cache_clear.cleared'));

        return self::SUCCESS;
    }
}
