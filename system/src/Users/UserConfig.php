<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

class UserConfig
{
    /** @var bool Allow direct external links */
    public bool $directUrl = false;

    public ?int $perPage = null;

    public ?string $lang = null;

    public ?string $timezone = null;

    public ?string $theme = null;

    public function __set($name, $value)
    {
        $this->$name = $value;
    }
}
