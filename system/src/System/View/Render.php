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

use InvalidArgumentException;
use Mobicms\Render\Engine;

/**
 * Note: render() is deliberately NOT overridden. It used to be, only to catch every Throwable
 * and return the exception message as the page body — which replaced the whole page with a raw
 * error string, leaked that message to visitors regardless of DEBUG, logged nothing, and still
 * answered 200. Failures now reach Kernel::handle(), which logs them and answers 500 with the
 * details only when DebugDetailsPolicy allows it. Templates execute inside that call, so the view
 * layer must not swallow what they raise.
 */
class Render extends Engine
{
    /** @var string */
    private $theme = 'default';

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    /**
     * Registers a template namespace.
     *
     * Registering the same namespace twice is a no-op instead of the InvalidArgumentException
     * Plates raises. The engine is a container singleton while every controller registers its
     * module namespace on construction, so the second request handled in one process used to die
     * with 'The template namespace "forum" is already being used.' — found by the functional
     * smoke set of plan stage 3a, and a hard blocker for the worker runtime of stage 6.
     *
     * Only a repeated registration of the very same directories is silent: a different directory
     * under a name already taken is a genuine clash between modules and still raises.
     */
    public function addFolder(string $name, string $directory, array $search = []): Engine
    {
        $searchFolder = [];
        if ($this->theme !== 'default') {
            $path = realpath(THEMES_PATH . $this->theme . '/templates/' . $name);
            if ($path !== false) {
                $searchFolder[] = $path;
            }
        }

        if ($this->folderIsRegistered($name, $directory, $searchFolder)) {
            return $this;
        }

        return parent::addFolder($name, $directory, $searchFolder);
    }

    /**
     * @param array<string> $search
     */
    private function folderIsRegistered(string $name, string $directory, array $search): bool
    {
        try {
            $registered = $this->getFolder($name);
        } catch (InvalidArgumentException) {
            return false;
        }

        return $registered === array_merge([rtrim($directory, '/\\')], $search);
    }
}
