<?php

return [
        'not_logged_in'            => 'Вы не авторизованы.',
        'you_dont_have_permission' => 'У вас нет разрешения на выполнение этого действия.',
        'you_dont_have_role'       => 'У вас нет необходимой роли для выполнения этого действия.',

        'commands' => [
                'cache_clear' => [
                        'description' => 'Очистить кэш разрешений',
                        'disabled'    => 'Кэш разрешений отключен или хранилище кэша не поддерживает теги.',
                        'cleared'     => 'Кэш разрешений успешно очищен.',
                ],

                'uninstall'   => [
                        'description'               => 'Удалить конфигурацию, миграции и данные пакета Permission',
                        'force'                     => 'Выполнить операцию принудительно, в том числе в production',
                        'config_removed'            => 'Удален config/permissions.php',
                        'migration_rolled_back'     => 'Миграция откачена: :path',
                        'migration_rollback_failed' => 'Не удалось откатить миграцию: :error',
                        'migration_deleted'         => 'Миграция удалена: :path',
                        'translations_removed'      => 'Удалено: :path',
                        'uninstalled'               => 'Пакет Permission успешно удален.',
                ],
        ],
];
