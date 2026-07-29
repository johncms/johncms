<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

/**
 * Who made the request: the facts about the visitor that the layers below HTTP need to store
 * or to check against, without depending on the request itself.
 *
 * Addresses are textual. The unsigned-int form the `ip` columns use is produced by the Ip cast
 * on the way to the database, so the day those columns become IPv6-capable nothing here changes.
 */
final readonly class ClientInfoDTO
{
    /**
     * @param string $ip The visitor address
     * @param string $ipViaProxy The address the visitor's own proxy forwards for; empty when there is none
     * @param string $userAgent
     */
    public function __construct(
        public string $ip,
        public string $ipViaProxy,
        public string $userAgent,
    ) {
    }
}
