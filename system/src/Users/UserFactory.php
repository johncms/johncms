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

class UserFactory
{
    /**
     * Builds the shared instance holding the current user: a guest, since the request it belongs
     * to is not known at container build time. It is CurrentUserAuthenticator that puts the
     * visitor into it, once per request.
     */
    public function __invoke(): User
    {
        return new User();
    }
}
