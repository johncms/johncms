<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

class Session
{
    protected const FLASH = '_flash_';

    public const SESSION_NAME = 'SESID';

    public function __invoke(): Session
    {
        $session = new self();
        $session->start();
        return $session;
    }

    public function start(): void
    {
        if (\PHP_SESSION_ACTIVE === session_status()) {
            throw new \RuntimeException('Failed to start the session: already started by PHP.');
        }

        session_name(self::SESSION_NAME);
        session_start();
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function flash(string $key, $value): void
    {
        $_SESSION[self::FLASH][$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getFlash(string $key)
    {
        if (isset($_SESSION[self::FLASH][$key])) {
            $value = $_SESSION[self::FLASH][$key];
            unset($_SESSION[self::FLASH][$key]);
            return $value;
        }
        return null;
    }

    /**
     * @param string|int $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * @param string|int $key
     * @param mixed $value
     */
    public function set($key, $value): void
    {
        $_SESSION[$key] = $value;
    }
}
