<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Events;

interface AuthEventLoggerInterface
{
    /**
     * @param int|null             $userId  Whom the event is about. Null when nobody was identified.
     * @param array<string, mixed> $context Whatever the event needs beyond the columns.
     * @param int|null             $actorId Who did it, when it is not the visitor of this request.
     *                                      Filled in from the current identity when omitted.
     */
    public function log(
        AuthEventType|string $event,
        ?int $userId = null,
        array $context = [],
        ?int $actorId = null,
        ?int $now = null,
    ): void;
}
