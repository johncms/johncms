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
}
