<?php

return [
        'not_logged_in'            => 'Siz tizimga kirmagansiz.',
        'you_dont_have_permission' => 'Bu amalni bajarish uchun sizda ruxsat yo\'q.',
        'you_dont_have_role'       => 'Bu amalni bajarish uchun kerakli rol sizda yo\'q.',

        'commands' => [
                'cache_clear' => [
                        'description' => 'Ruxsat keshlarini tozalash',
                        'disabled'    => 'Ruxsat keshi o\'chirilgan yoki kesh xotirasi teglarni qo\'llab-quvvatlamaydi.',
                        'cleared'     => 'Ruxsat keshlari muvaffaqiyatli tozalandi.',
                ],

                'uninstall'   => [
                        'description'               => 'Permission paketiga tegishli konfiguratsiya, migratsiya va ma\'lumotlarni o\'chirish',
                        'force'                     => 'Amalni production muhitida ham majburan bajarish',
                        'config_removed'            => 'config/permissions.php o\'chirildi',
                        'migration_rolled_back'     => 'Migratsiya qaytarildi: :path',
                        'migration_rollback_failed' => 'Migratsiyani qaytarib bo\'lmadi: :error',
                        'migration_deleted'         => 'Migratsiya o\'chirildi: :path',
                        'translations_removed'      => ':path o\'chirildi',
                        'uninstalled'               => 'Permission paketi muvaffaqiyatli o\'chirildi.',
                ],
        ],
];
