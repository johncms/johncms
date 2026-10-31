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
 * Where a menu item belongs: the main menu a visitor navigates the site by, its own part for the
 * staff, the menu an administrator works in, and the quick actions on the dashboard of the panel.
 */
enum MenuArea: string
{
    case Main = 'main';

    /**
     * The lines of the main menu kept for the staff — the panel, the chat of the administration —
     * drawn apart from the sections of the site.
     */
    case Staff = 'staff';

    case Admin = 'admin';

    /**
     * The buttons at the top of the dashboard of the panel: what the staff do often — write the
     * news, find somebody — a click away.
     */
    case AdminActions = 'admin_actions';
}
