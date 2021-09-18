<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\View;

use Mobicms\Render\Engine;

class Render extends Engine
{
    private string $theme = 'default';

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function addFolder(string $name, string $directory): Engine
    {
        $this->addPath($directory, $name);

        if ($this->theme !== 'default' && $this->theme !== 'admin') {
            $themePath = realpath(THEMES_PATH . $this->theme . '/templates/' . $name);
            if ($themePath !== false) {
                $this->addPath($themePath, $name);
            }
        }

        return $this;
    }
}
