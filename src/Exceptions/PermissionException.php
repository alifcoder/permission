<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 4:38 PM
 */

namespace Alif\Permissions\Exceptions;

use Exception;

class PermissionException extends Exception
{
    public static function roles(): self
    {
        return new static(message: __('permissions::permissions.you_dont_have_role'), code: 403);
    }

    public static function permissions(): self
    {
        return new static(message: __('permissions::permissions.you_dont_have_permission'), code: 403);
    }

    public static function notLoggedIn(): self
    {
        return new static(message: __('permissions::permissions.not_logged_in'), code: 401);
    }
}
