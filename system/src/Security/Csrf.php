<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Security;

use Johncms\Http\Session;
use Psr\Container\ContainerInterface;

class Csrf
{
    public const SESSION_NAMESPACE = '_csrf';

    public const DEFAULT_TOKEN_ID = '_token';

    protected Session $session;

    public function __construct(ContainerInterface $container)
    {
        $this->session = $container->get(Session::class);
    }

    public function __invoke(): self
    {
        return $this;
    }

    /**
     * Get the generated token
     */
    public function getToken(string $token_id = self::DEFAULT_TOKEN_ID): string
    {
        $sessionKey = self::SESSION_NAMESPACE . '.' . $token_id;
        if (! $this->session->has($sessionKey)) {
            $this->refreshToken($token_id);
        }

        return (string) $this->session->get($sessionKey);
    }

    /**
     * Refresh token
     */
    public function refreshToken(string $token_id = self::DEFAULT_TOKEN_ID): void
    {
        $sessionKey = self::SESSION_NAMESPACE . '.' . $token_id;
        $this->session->set($sessionKey, $this->generateToken());
    }

    /**
     * Generate token
     */
    public function generateToken(): string
    {
        return uniqid('', true);
    }
}
