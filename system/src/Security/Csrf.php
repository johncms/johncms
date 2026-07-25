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

class Csrf
{
    public const SESSION_NAMESPACE = '_csrf';

    public const DEFAULT_TOKEN_ID = '_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function __invoke(): self
    {
        return $this;
    }

    public static function create(Session $session): self
    {
        return new self($session);
    }

    /**
     * Get the generated token
     *
     * @param string $token_id
     * @return mixed
     */
    public function getToken(string $token_id = self::DEFAULT_TOKEN_ID)
    {
        $key = self::SESSION_NAMESPACE . '.' . $token_id;
        if (empty($this->session->get($key))) {
            $this->refreshToken($token_id);
        }

        return $this->session->get($key);
    }

    /**
     * Refresh token
     *
     * @param string $token_id
     */
    public function refreshToken(string $token_id = self::DEFAULT_TOKEN_ID): void
    {
        $this->session->set(self::SESSION_NAMESPACE . '.' . $token_id, $this->generateToken());
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
