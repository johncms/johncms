<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail\Queue;

/**
 * What one pass over the mail queue did.
 */
final readonly class MailQueueRunDTO
{
    public function __construct(
        public int $sent = 0,
        public int $retrying = 0,
        public int $failed = 0,
    ) {
    }

    public function processed(): int
    {
        return $this->sent + $this->retrying + $this->failed;
    }
}
