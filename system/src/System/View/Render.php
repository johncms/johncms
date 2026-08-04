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
    /** @var (callable(): string)|null */
    private $themeResolver = null;

    /**
     * Sets how the current theme name is obtained.
     *
     * A resolver rather than a name: the engine is a container singleton, and the theme depends
     * on the request being served, so a name captured at construction would be the wrong one for
     * every request but the first under a long-running runtime. The resolver is called when a
     * template is being rendered, which is always inside a request.
     *
     * @param callable(): string $resolver
     */
    public function setThemeResolver(callable $resolver): void
    {
        $this->themeResolver = $resolver;
    }

    /**
     * Registers a template namespace.
     *
     * Registering the same namespace twice is a no-op instead of the InvalidArgumentException
     * Plates raises. The engine is a container singleton while every controller registers its
     * module namespace on construction, so the second request handled in one process used to die
     * with 'The template namespace "forum" is already being used.' — found by the functional
     * smoke set, and a hard blocker for a worker runtime.
     *
     * Only a repeated registration of the very same directory is silent: a different directory
     * under a name already taken is a genuine clash between modules and still raises.
     */
    public function addFolder(string $name, string $directory, array $search = []): Engine
    {
        if ($this->folderIsRegistered($name, $directory, $search)) {
            return $this;
        }

        return parent::addFolder($name, $directory, $search);
    }

    /**
     * Returns the search paths of a namespace, the current theme first.
     *
     * The theme path is prepended here and not in addFolder() because it is only known once a
     * request is being served. Plates walks the list backwards, so the last entry wins.
     *
     * @return array<string>
     */
    public function getFolder(string $name): array
    {
        $folders = parent::getFolder($name);
        $themePath = $this->themePath($name);

        return $themePath === null ? $folders : array_merge($folders, [$themePath]);
    }

    private function themePath(string $namespace): ?string
    {
        if ($this->themeResolver === null) {
            return null;
        }

        $theme = ($this->themeResolver)();
        if ($theme === '' || $theme === 'default') {
            return null;
        }

        $path = realpath(THEMES_PATH . $theme . '/templates/' . $namespace);

        return $path === false ? null : $path;
    }

    /**
     * @param array<string> $search
     */
    private function folderIsRegistered(string $name, string $directory, array $search): bool
    {
        try {
            $registered = parent::getFolder($name);
        } catch (InvalidArgumentException) {
            return false;
        }

        return $registered === array_merge([rtrim($directory, '/\\')], $search);
    }
}
