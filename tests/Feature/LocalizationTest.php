<?php

namespace Alif\Permissions\Tests\Feature;

use Alif\Permissions\Exceptions\PermissionException;
use Alif\Permissions\Tests\TestCase;
use Illuminate\Support\Arr;

class LocalizationTest extends TestCase
{
    public function test_every_english_key_is_translated_to_every_locale(): void
    {
        $english = Arr::dot(require __DIR__ . '/../../resources/lang/en/permissions.php');

        $this->assertNotEmpty($english);

        foreach (['uz', 'ru'] as $locale) {
            $translated = Arr::dot(require __DIR__ . "/../../resources/lang/{$locale}/permissions.php");

            $this->assertSame(array_keys($english), array_keys($translated), "keys of [{$locale}] differ from [en]");
        }
    }

    public function test_the_exceptions_are_translated_to_russian(): void
    {
        $this->app->setLocale('ru');

        $this->assertSame('Вы не авторизованы.', PermissionException::notLoggedIn()->getMessage());
    }

    public function test_the_exceptions_are_translated_to_the_application_locale(): void
    {
        $this->app->setLocale('uz');

        $this->assertSame('Siz tizimga kirmagansiz.', PermissionException::notLoggedIn()->getMessage());
        $this->assertSame('Bu amalni bajarish uchun sizda ruxsat yo\'q.', PermissionException::permissions()->getMessage());
    }

    public function test_the_console_messages_are_translated_to_the_application_locale(): void
    {
        $this->app->setLocale('uz');

        $this->artisan('permission:cache-clear')
                ->expectsOutputToContain('Ruxsat keshlari muvaffaqiyatli tozalandi.')
                ->assertSuccessful();
    }

    public function test_a_missing_translation_falls_back_to_english(): void
    {
        $this->app->setLocale('de');

        $this->assertSame('You are not logged in.', PermissionException::notLoggedIn()->getMessage());
    }
}
