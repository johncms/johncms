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

final readonly class LegacyRightsMigrationReport
{
    /**
     * @param int                    $granted      Accounts that were given a role.
     * @param int                    $skipped      Accounts left alone because they already had
     *                                             roles arranged for them.
     * @param array<int, list<int>>  $unrecognised Account ids per rights value that matches no
     *                                             documented role. These were mapped down and
     *                                             have to be looked at by hand.
     */
    public function __construct(
        public int $granted = 0,
        public int $skipped = 0,
        public array $unrecognised = [],
    ) {
    }

    public function hasUnrecognised(): bool
    {
        return $this->unrecognised !== [];
    }
}
