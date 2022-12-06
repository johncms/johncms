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

use Illuminate\Support\Arr;

class Session
{
    protected const FLASH = '_flash_';

    public const SESSION_NAME = 'SESID';

    public function __invoke(): Session
    {
        $this->start();
        return $this;
    }

    public function start(): void
    {
        if (\PHP_SESSION_ACTIVE === session_status()) {
            throw new \RuntimeException('Failed to start the session: already started by PHP.');
        }

        if (! headers_sent()) {
            session_name(self::SESSION_NAME);
            session_start();
        }

        if (! isset($_SESSION) || PHP_SAPI === 'cli') {
            $_SESSION = [];
        }
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION[self::FLASH][$key] = $value;
    }

    public function getFlash(string $key): mixed
    {
        if (isset($_SESSION[self::FLASH][$key])) {
            $value = $_SESSION[self::FLASH][$key];
            unset($_SESSION[self::FLASH][$key]);
            return $value;
        }
        return null;
    }

    /**
     * @param mixed|null $default
     * @psalm-suppress NullReference
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($_SESSION, $key, $default);
    }

    /**
     * @psalm-suppress NullReference
     */
    public function set(string $key, mixed $value): void
    {
        Arr::set($_SESSION, $key, $value);
    }

    public function has(string $key): bool
    {
        return Arr::has($_SESSION, $key);
    }

    /**
     * @psalm-suppress NullReference
     */
    public function remove(array|string $key): void
    {
        Arr::forget($_SESSION, $key);
    }
}
