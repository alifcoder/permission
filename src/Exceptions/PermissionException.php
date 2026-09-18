<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 4:38 PM
 */

namespace Alif\Permissions\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionException extends Exception
{
    public static function roles(): self
    {
        return new static(message: __('permissions::permissions.you_dont_have_role'), code: Response::HTTP_FORBIDDEN);
    }

    public static function permissions(): self
    {
        return new static(message: __('permissions::permissions.you_dont_have_permission'), code: Response::HTTP_FORBIDDEN);
    }

    public static function notLoggedIn(): self
    {
        return new static(message: __('permissions::permissions.not_logged_in'), code: Response::HTTP_UNAUTHORIZED);
    }

    /**
     * HTTP status code of the exception.
     */
    public function getStatusCode(): int
    {
        $code = (int)$this->getCode();

        return $code >= 400 && $code <= 599 ? $code : Response::HTTP_FORBIDDEN;
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], $this->getStatusCode());
        }

        return response($this->getMessage(), $this->getStatusCode());
    }
}
