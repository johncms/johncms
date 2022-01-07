<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users\Middlewares;

use Johncms\Users\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class HasPermissionMiddleware implements MiddlewareInterface
{
    public function __construct(protected array $permissions = [])
    {
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = di(User::class);
        if (! $user?->hasPermission($this->permissions)) {
            return status_page(403);
        }

        return $handler->handle($request);
    }
}
