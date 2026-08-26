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
 * Where a menu item belongs. Two menus, because there are two: the one a visitor navigates the
 * site by, and the one an administrator works in.
 */
enum MenuArea: string
{
    case Main = 'main';

    case Admin = 'admin';
}
