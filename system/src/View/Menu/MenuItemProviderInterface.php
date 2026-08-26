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
 * How a module gets itself into a menu.
 *
 * Without this an installed module is reachable only by typing its address: both menus are
 * templates of the theme, and a module has no business editing a theme. A module implements this,
 * the container tags it, and the menus draw what it returns.
 */
interface MenuItemProviderInterface
{
    /**
     * @return iterable<MenuItem>
     */
    public function menuItems(): iterable;
}
