<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

/**
 * Whether the visitor may open the profile of an account, and the address to lead them there.
 *
 * Every page that names an author asks here instead of building "/profile/<id>" itself: a link
 * the visitor cannot follow is a dead end for them and a refused address for a crawler, which
 * comes as a guest.
 */
interface ProfileLinksInterface
{
    public function canView(int $userId): bool;

    /**
     * Null when there is nothing the visitor could open: the author has no account, the visitor
     * may not see it or the site has no profiles.
     */
    public function urlOf(int $userId): ?string;
}
