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

interface AntifloodCheckerInterface
{
    /**
     * Returns the number of seconds the current user still has to wait before posting again.
     * Zero means there is no flood limit in effect.
     */
    public function getRemainingSeconds(): int;
}
