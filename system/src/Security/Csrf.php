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
     *
     * @param string $token_id
     * @return mixed
     */
    public function getToken(string $token_id = self::DEFAULT_TOKEN_ID)
    {
        $session_key = self::SESSION_NAMESPACE . '.' . $token_id;
        if (! $this->session->has($session_key)) {
            $this->refreshToken($token_id);
        }

        return $this->session->get($session_key);
    }

    /**
     * Refresh token
     *
     * @param string $token_id
     */
    public function refreshToken(string $token_id = self::DEFAULT_TOKEN_ID): void
    {
        $session_key = self::SESSION_NAMESPACE . '.' . $token_id;
        $this->session->set($session_key, $this->generateToken());
    }

    /**
     * Generate token
     *
     * @return string
     */
    public function generateToken(): string
    {
        return uniqid('', true);
    }
}
