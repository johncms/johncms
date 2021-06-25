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

use Johncms\Users\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    protected array $rights;

    public function __construct(array $rights = [])
    {
        $this->rights = $rights;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = di(User::class);
        if ((empty($this->rights) && $user->isValid()) || (! empty($this->rights) && in_array($user->rights, $this->rights))) {
            return $handler->handle($request);
        }
        return access_denied();
    }
}
