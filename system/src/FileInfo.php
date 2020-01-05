<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

use Johncms\System\Legacy\Tools;
use SplFileInfo;

class FileInfo extends SplFileInfo
{

    /**
     * File name without extension
     *
     * @return string
     */
    public function getNameWithoutExtension(): string
    {
        return $this->getBasename('.' . $this->getExtension());
    }

    /**
     * Get cleared file name
     *
     * @return string|string[]|null
     */
    public function getCleanName()
    {
        /** @var Tools $tools */
        $tools = di(Tools::class);
        $name = $tools->rusLat($this->getNameWithoutExtension());

        $name = preg_replace('~[^-a-zA-Z0-9_]+~u', '_', $name);
        $name = trim($name, '_');
        $name = preg_replace('/-{2,}/', '_', $name);
        $name = mb_substr($name, 0, 150) . '.' . $this->getExtension();

        return $name;
    }

    /**
     * Get public file path
     *
     * @return string
     */
    public function getPublicPath(): string
    {
        return pathToUrl($this->getRealPath());
    }
}
