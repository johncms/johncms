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
 * Short-term log of who has been hitting the site: one entry per request, kept for a minute.
 *
 * It is what the "IP activity" page of the online module reports on. Addresses are passed and
 * returned in their textual form — how they are stored is the business of the implementation.
 */
interface RequestRateLogInterface
{
    /**
     * Records one request from the given address and drops the entries older than the retention
     * window.
     */
    public function record(string $ip): void;

    /**
     * The addresses seen during the retention window, one entry per request, so that repeated
     * requests from the same address appear as many times as they were made.
     *
     * @return list<string>
     */
    public function recentAddresses(): array;
}
