<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules;

class Modules
{
    /** @var array */
    protected $config;

    public function __construct()
    {
        $this->config = config('modules', []);
    }

    public function getInstalled(): array
    {
        return array_merge($this->config['installed_modules'], $this->config['system_modules']);
    }
}
