<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\View\Menu;

/**
 * One line in a menu, as the module declaring it describes it.
 *
 * The title is already translated: a module knows its own domain, and the menu is drawn long after
 * the domain of the page has been decided.
 */
final readonly class MenuItem
{
    /**
     * @param string      $title      Translated — d__('blog', 'Blog').
     * @param string      $url        Where it leads.
     * @param string|null $icon       An id in the sprite of the theme ("book"), or the address of
     *                                an image the module ships. Null draws no icon.
     * @param string|null $permission The item is drawn only for a visitor holding this.
     * @param int         $weight     Lighter items float up; equal weights keep alphabetical order.
     */
    public function __construct(
        public MenuArea $area,
        public string $title,
        public string $url,
        public ?string $icon = null,
        public ?string $permission = null,
        public int $weight = 100,
    ) {
    }

    /**
     * Whether the icon is an address rather than an id in the sprite of the theme.
     */
    public function hasImageIcon(): bool
    {
        return $this->icon !== null && (str_starts_with($this->icon, '/') || str_contains($this->icon, '://'));
    }
}
