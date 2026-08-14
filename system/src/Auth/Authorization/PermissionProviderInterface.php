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

/**
 * A module declares the permissions it checks by registering a provider with the
 * `johncms.auth.permissions` tag.
 */
interface PermissionProviderInterface
{
    /**
     * @return iterable<PermissionDefinition>
     */
    public function permissions(): iterable;
}
