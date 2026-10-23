<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-17
 * Contact: https://t.me/alif_coder
 * Time: 1:05 PM
 */

return [
        'not_logged_in'            => 'You are not logged in.',
        'you_dont_have_permission' => 'You do not have permission to perform this action.',
        'you_dont_have_role'       => 'You do not have the required role to perform this action.',

        'commands' => [
                'cache_clear' => [
                        'description' => 'Clear the permission caches',
                        'disabled'    => 'Permission cache is disabled or the cache store does not support tags.',
                        'cleared'     => 'Permission caches are cleared successfully.',
                ],

                'uninstall'   => [
                        'description'               => 'Remove config, migrations, and data related to Permission package',
                        'force'                     => 'Force the operation to run when in production',
                        'config_removed'            => 'Removed config/permissions.php',
                        'migration_rolled_back'     => 'Rolled back migration in: :path',
                        'migration_rollback_failed' => 'Failed to rollback migration: :error',
                        'migration_deleted'         => 'Deleted migration: :path',
                        'translations_removed'      => 'Removed :path',
                        'uninstalled'               => 'Permission package uninstalled successfully.',
                ],
        ],
];
