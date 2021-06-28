<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Middlewares;

use Johncms\Http\Request;
use Johncms\Security\Csrf;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Starting a session and generate a csrf token
        if ($request->getMethod() === 'POST' && $this->needToValidate()) {
            if (! $this->validateCsrfToken()) {
                return status_page(419);
            }
        }

        return $handler->handle($request);
    }

    private function validateCsrfToken(): bool
    {
        // 419 Page expired
        $request = di(Request::class);
        $csrf = di(Csrf::class);
        $request_token = $request->getPost('csrf_token');
        if (! $request_token) {
            $request_token = $request->getHeader('X-CSRF-Token');
        }

        return $csrf->getToken() === $request_token;
    }

    private function needToValidate(): bool
    {
        $excepts = di('config')['csrf']['excepts'] ?? [];
        if (! empty($excepts)) {
            $request_path = di(Request::class)->getUri()->getPath();
            foreach ($excepts as $pattern) {
                $pattern = preg_quote($pattern, '#');
                $pattern = str_replace('\*', '.*', $pattern);
                if (preg_match('#^' . $pattern . '\z#u', $request_path) === 1) {
                    return false;
                }
            }
        }

        return true;
    }
}
