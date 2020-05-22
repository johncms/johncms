<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Security;

class Csrf
{
    public const SESSION_NAMESPACE = '_csrf';

    public const DEFAULT_TOKEN_ID = '_token';

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
        if (empty($_SESSION[self::SESSION_NAMESPACE][$token_id])) {
            $this->refreshToken($token_id);
        }

        return $_SESSION[self::SESSION_NAMESPACE][$token_id];
    }

    /**
     * Refresh token
     *
     * @param string $token_id
     */
    public function refreshToken(string $token_id = self::DEFAULT_TOKEN_ID): void
    {
        $_SESSION[self::SESSION_NAMESPACE][$token_id] = $this->generateToken();
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
