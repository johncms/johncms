<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\View;

use Mobicms\Render\Engine;
use Throwable;

class Render extends Engine
{
    /** @var string */
    private $theme = 'default';

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function addFolder(string $name, string $directory, array $search = []): Engine
    {
        $searchFolder = $this->theme !== 'default'
            ? [realpath(THEMES_PATH . $this->theme . '/templates/' . $name)]
            : [];

        return parent::addFolder($name, $directory, $searchFolder);
    }

    /**
     * Render the template
     *
     * @param string $name
     * @param array $data
     * @return string
     */
    public function render(string $name, array $data = []): string
    {
        try {
            return parent::render($name, $data);
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
