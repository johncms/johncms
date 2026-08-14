<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization;

use Johncms\Auth\Identity;

/**
 * The one place that answers whether something is allowed.
 *
 * Always injected, never reached through di(): a module has to be able to replace it with a
 * stub in its own tests, and a static call would take that away.
 */
interface AccessCheckerInterface
{
    /**
     * Whether the visitor of the current request may do this. The subject narrows the question
     * to one object — a forum section, a post, a profile — for the voters that understand it.
     */
    public function allows(string $permission, mixed $subject = null): bool;

    /**
     * The same question about somebody who is not the current visitor: an administrator
     * checking what a user would be allowed, a background job acting for an account.
     */
    public function allowsFor(Identity $identity, string $permission, mixed $subject = null): bool;
}
