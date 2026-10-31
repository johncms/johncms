<?php

declare(strict_types=1);

namespace Johncms\Mail\Queue;

interface EmailQueueStatusInterface
{
    /** @return array{failed: int, overdue: int} */
    public function counts(int $overdueBefore): array;
}
