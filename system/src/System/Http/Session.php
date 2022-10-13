<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Http;

use Illuminate\Support\Arr;

class Session
{
    protected const FLASH_PREFIX = '_flash_';

    /**
     * @param string $key
     * @param mixed $value
     */
    public function flash(string $key, $value): void
    {
        $_SESSION[self::FLASH_PREFIX . $key] = $value;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getFlash(string $key)
    {
        $value = $_SESSION[self::FLASH_PREFIX . $key] ?? null;
        unset($_SESSION[self::FLASH_PREFIX . $key]);
        return $value;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @psalm-suppress NullReference
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($_SESSION, $key, $default);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @psalm-suppress NullReference
     */
    public function set(string $key, $value): void
    {
        Arr::set($_SESSION, $key, $value);
    }

    public function has(string $key): bool
    {
        return Arr::has($_SESSION, $key);
    }

    /**
     * @param array|string $key
     * @psalm-suppress NullReference
     */
    public function remove($key): void
    {
        Arr::forget($_SESSION, $key);
    }
}
