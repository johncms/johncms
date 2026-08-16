<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Impersonation;

/**
 * How browsing as somebody else behaves, resolved from config once per process.
 */
final readonly class ImpersonationSettings
{
    /**
     * @param string       $parentCookieName  Where the administrator's own session waits.
     * @param int          $lifetime          How long it lasts, in seconds. Never extended.
     * @param list<string> $deniedPermissions Refused while browsing as somebody else, whatever
     *                                        the roles say.
     */
    public function __construct(
        public string $parentCookieName = 'jc_auth_parent',
        public int $lifetime = 3600,
        public array $deniedPermissions = [],
    ) {
    }
}
