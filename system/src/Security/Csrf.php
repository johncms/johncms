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
     */
    public function getToken(string $token_id = self::DEFAULT_TOKEN_ID): string
    {
        // The tokens are kept as one nested array under a single key: the session facade stores
        // flat keys and does not resolve dot notation.
        $tokens = $this->session->get(self::SESSION_NAMESPACE, []);

        return empty($tokens[$token_id]) ? $this->refreshToken($token_id) : $tokens[$token_id];
    }

    /**
     * Refresh token
     *
     * @return string The freshly generated token
     */
    public function refreshToken(string $token_id = self::DEFAULT_TOKEN_ID): string
    {
        $token = $this->generateToken();

        $tokens = $this->session->get(self::SESSION_NAMESPACE, []);
        $tokens[$token_id] = $token;
        $this->session->set(self::SESSION_NAMESPACE, $tokens);

        return $token;
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
