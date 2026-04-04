<?php

declare(strict_types=1);

namespace Johncms\Scheduler;

interface ScheduleMutexInterface
{
    public function acquire(string $key): mixed;

    public function release(mixed $lock): void;
}
